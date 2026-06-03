<?php
// rank_whales.php — CLI scanner: finds best diamond-hand holder whales
// Run:  php rank_whales.php
// Writes: data/top_whales.json
$HELIUS_KEY = 'fe14718f-05d3-49d2-9880-7d0022cdbf84';
$DIR = __DIR__;
$STABLES = [
  'So11111111111111111111111111111111111111112', // wSOL
  'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v', // USDC
  'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB', // USDT
];
$CANDIDATES = 40;  // how many top-alpha whales to deep-scan
$TOPN = 10;        // final list size

$raw = json_decode(file_get_contents("$DIR/data/discovered_whales.json"), true);
$whales = array_values($raw);
// sort by alpha (coins caught) desc, take candidates
usort($whales, fn($a,$b)=>count($b['coins_caught']??[]) - count($a['coins_caught']??[]));
$cands = array_slice($whales, 0, $CANDIDATES);
echo "Scanning ".count($cands)." top-alpha whales via Helius...\n";

function helius_holdings($wallet, $key) {
    $payload = json_encode([
        'jsonrpc'=>'2.0','id'=>1,'method'=>'getAssetsByOwner',
        'params'=>['ownerAddress'=>$wallet,'page'=>1,'limit'=>100,
            'displayOptions'=>['showFungible'=>true,'showNativeBalance'=>true]],
    ]);
    $ch=curl_init("https://mainnet.helius-rpc.com/?api-key=$key");
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_TIMEOUT=>40]);
    $res=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if($code!==200||!$res) return null;
    return json_decode($res,true);
}

$results = [];
foreach ($cands as $i=>$w) {
    $addr=$w['address'];
    $alpha=count($w['coins_caught']??[]);
    $d=helius_holdings($addr,$HELIUS_KEY);
    if(!$d){ echo "  [".($i+1)."] ".substr($addr,0,6)."... FAILED\n"; usleep(300000); continue; }
    $items=$d['result']['items']??[];
    $nb=$d['result']['nativeBalance']??[];
    $solVal=$nb['total_price']??0;
    $totalVal=$solVal; $memeHolds=0; $memeVal=0; $topHold=['sym'=>'','val'=>0];
    foreach($items as $it){
        $iface=$it['interface']??'';
        if($iface!=='FungibleToken'&&$iface!=='FungibleAsset') continue;
        $mint=$it['id']??'';
        $ti=$it['token_info']??[];
        $val=$ti['price_info']['total_price']??0;
        if($val<1) continue;
        $totalVal+=$val;
        if(in_array($mint,$GLOBALS['STABLES'])) continue;
        if($val>30){
            $memeHolds++; $memeVal+=$val;
            $sym=$ti['symbol']??($it['content']['metadata']['symbol']??'?');
            if($val>$topHold['val']) $topHold=['sym'=>$sym,'val'=>$val];
        }
    }
    // Diamond-hand composite score
    $score = ($alpha*0.4) + ($memeHolds*15) + (min($memeVal,8000)/40) + (min($totalVal,80000)/800);
    $results[]=[
        'address'=>$addr,
        'label'=>$w['label']??('Whale'),
        'alpha'=>$alpha,
        'total_value'=>round($totalVal,2),
        'meme_holds'=>$memeHolds,
        'meme_value'=>round($memeVal,2),
        'top_hold'=>$topHold['sym'].' ($'.round($topHold['val']).')',
        'score'=>round($score,1),
    ];
    echo "  [".($i+1)."] ".substr($addr,0,6)."... alpha=$alpha holds=$memeHolds memeVal=$".round($memeVal)." total=$".round($totalVal)." score=".round($score,1)."\n";
    usleep(300000); // 0.3s — rate-limit safe
}

// rank by score desc
usort($results, fn($a,$b)=>$b['score']<=>$a['score']);
$top = array_slice($results, 0, $TOPN);
foreach($top as $k=>&$t) $t['rank']=$k+1;
unset($t);

$out=['success'=>true,'updated_at'=>date('Y-m-d H:i:s'),'scanned'=>count($results),'top'=>$top];
file_put_contents("$DIR/data/top_whales.json", json_encode($out, JSON_PRETTY_PRINT));
echo "\n==== TOP $TOPN DIAMOND-HAND WHALES ====\n";
foreach($top as $t){
    echo "#{$t['rank']}  {$t['label']}  ".substr($t['address'],0,6)."...  score={$t['score']}  | alpha={$t['alpha']} holds={$t['meme_holds']} memeVal=\${$t['meme_value']} total=\${$t['total_value']} | top: {$t['top_hold']}\n";
}
echo "\nSaved -> data/top_whales.json\n";
