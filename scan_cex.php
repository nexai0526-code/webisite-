<?php
// scan_cex.php — CLI: checks which CEXs list the coins whales are buying
// Run:  php scan_cex.php
// Writes: data/cex_radar.json
$DIR = __DIR__;
$BATCHES = 6;        // whale_signals batches to pull coins from
$MAX_COINS = 22;     // CoinGecko coins to scan (rate-limit safe)
$CG = 'https://api.coingecko.com/api/v3';

// Tier classification by CoinGecko market identifier
$TIER1 = ['binance','gdax','upbit','okex','kraken','bybit_spot'];      // gdax = Coinbase
$TIER2 = ['mxc','gate','kucoin','bitget','htx','huobi','bingx','bitmart','lbank','probit'];

function http_get($url){
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>25,
        CURLOPT_HTTPHEADER=>['accept: application/json','user-agent: CallGod/1.0']]);
    $r=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    return [$code,$r];
}

// 1. Collect coins whales are buying across batches
echo "Collecting whale-bought coins...\n";
$coins = []; // mint => ['coin'=>sym,'buy_count'=>n]
for($b=0;$b<$BATCHES;$b++){
    [$code,$r]=http_get("https://nexaionline.cloud/whale_signals.php?batch=$b");
    if($code!==200||!$r) continue;
    $d=json_decode($r,true);
    foreach(($d['alpha_signals']??[]) as $s){
        $mint=$s['mint']??''; if(!$mint) continue;
        $bc=$s['buy_count']??0;
        if(!isset($coins[$mint])||$bc>$coins[$mint]['buy_count'])
            $coins[$mint]=['coin'=>ltrim($s['coin']??'?','$'),'buy_count'=>$bc,'mint'=>$mint];
    }
    usleep(200000);
}
// sort by buy_count desc, take top MAX_COINS
$list=array_values($coins);
usort($list,fn($a,$b)=>$b['buy_count']-$a['buy_count']);
$list=array_slice($list,0,$MAX_COINS);
echo "Scanning ".count($list)." coins on CoinGecko...\n";

$results=[];
foreach($list as $i=>$c){
    $mint=$c['mint'];
    $url="$CG/coins/solana/contract/$mint?tickers=true&market_data=true&community_data=false&developer_data=false&sparkline=false";
    [$code,$r]=http_get($url);
    if($code===429){ echo "  rate-limited, waiting 20s...\n"; sleep(20); [$code,$r]=http_get($url); }
    $onCG=false;$mcap=0;$vol=0;$cexNames=[];$tier1=false;$tier2=false;$exCount=0;
    if($code===200 && $r){
        $j=json_decode($r,true);
        if(isset($j['id'])){
            $onCG=true;
            $mcap=$j['market_data']['market_cap']['usd']??0;
            $vol=$j['market_data']['total_volume']['usd']??0;
            $seen=[];
            foreach(($j['tickers']??[]) as $t){
                $id=$t['market']['identifier']??'';
                $nm=$t['market']['name']??$id;
                if(!$id||isset($seen[$id])) continue;
                $seen[$id]=1;
                // skip DEXs (they have 'has_trading_incentive' false + identifier like raydium etc, but CG marks cex via trust)
                if(in_array($id,$GLOBALS['TIER1'])){ $tier1=true; $cexNames[]=$nm.' ⭐'; }
                elseif(in_array($id,$GLOBALS['TIER2'])){ $tier2=true; $cexNames[]=$nm; }
            }
            $exCount=count($cexNames);
        }
    }
    // classify
    if(!$onCG){ $status='⏳ EARLY'; $detail='DEX only — not on CoinGecko yet'; $color='#7B8794'; $score=10; }
    elseif($tier1){ $status='✅ LISTED'; $detail='Already on Tier-1'; $color='#14F195'; $score=5; }
    elseif($tier2){ $status='🔥 TIER-1 CANDIDATE'; $detail='On Tier-2 CEX → Tier-1 next?'; $color='#FF4757'; $score=80 + min($exCount*5,20) + ($mcap>20e6?15:0); }
    else { $status='📈 ON CG'; $detail='Listed, DEX/small CEX'; $color='#FF9F43'; $score=30 + ($mcap>10e6?10:0); }

    $results[]=[
        'coin'=>$c['coin'],'mint'=>$mint,'buy_count'=>$c['buy_count'],
        'on_cg'=>$onCG,'market_cap'=>round($mcap),'volume_24h'=>round($vol),
        'cex_count'=>$exCount,'cexes'=>array_slice($cexNames,0,6),
        'tier1'=>$tier1,'tier2'=>$tier2,
        'status'=>$status,'detail'=>$detail,'color'=>$color,'score'=>$score,
        'dex_url'=>"https://dexscreener.com/solana/$mint",
    ];
    echo "  [".($i+1)."] ".$c['coin']." — $status (cex=$exCount mcap=$".round($mcap/1000)."k)\n";
    sleep(2); // CoinGecko keyless rate-limit safe
}

// sort: candidates first (score desc)
usort($results,fn($a,$b)=>$b['score']<=>$a['score']);
$out=['success'=>true,'updated_at'=>date('Y-m-d H:i:s'),'scanned'=>count($results),'coins'=>$results];
file_put_contents("$DIR/data/cex_radar.json",json_encode($out,JSON_PRETTY_PRINT));
echo "\n==== CEX LISTING RADAR ====\n";
foreach($results as $r){
    echo str_pad($r['status'],22)." ".str_pad($r['coin'],14)." cex=".$r['cex_count']." mcap=$".round($r['market_cap']/1000)."k";
    if($r['cexes']) echo " [".implode(', ',$r['cexes'])."]";
    echo "\n";
}
echo "\nSaved -> data/cex_radar.json\n";
