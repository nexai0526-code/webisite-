<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ETHERSCAN_KEY = 'TXMMKSSBMMYF1FF7DGNRHE4ASW2WIKWS43';
$ALCHEMY_KEY = 'Pzh6P5a3Bjb69lFfn8dx0';

$cacheFile = __DIR__ . '/data/new_coin_whales.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 180) {
    echo file_get_contents($cacheFile);
    exit;
}

function fetchJson($url, $timeout = 5, $postData = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NexAI/1.0');
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

$alchemyUrl = "https://eth-mainnet.g.alchemy.com/v2/{$ALCHEMY_KEY}";
$WETH = '0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2';
$STABLES = [
    '0xdac17f958d2ee523a2206206994597c13d831ec7',
    '0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48',
    '0x6b175474e89094c44da98b954eedeac495271d0f',
];

// =========== STEP 1: Multi-source ETH new tokens ===========
$ethTokens = [];

// Get latest block
$blockData = fetchJson($alchemyUrl, 4, [
    'id'=>1,'jsonrpc'=>'2.0','method'=>'eth_blockNumber'
]);
$latestBlock = isset($blockData['result']) ? hexdec($blockData['result']) : 0;

// Source 1: Alchemy Uniswap V2 PairCreated (paginate 500 blocks = ~2h)
if ($latestBlock > 0) {
    for ($i = 0; $i < 50; $i++) {
        $chunkEnd = $latestBlock - ($i * 10);
        $chunkStart = $chunkEnd - 9;
        $chunkData = fetchJson($alchemyUrl, 4, [
            'id'=>1,'jsonrpc'=>'2.0','method'=>'eth_getLogs',
            'params'=>[[
                'fromBlock'=>'0x'.dechex($chunkStart),
                'toBlock'=>'0x'.dechex($chunkEnd),
                'address'=>'0x5C69bEe701ef814a2B6a3EDD4B1652CB9cc5aA6f',
                'topics'=>['0x0d3648bd0f6ba80134a33ba9275ac585d9d315f0ad8355cddefde31afa28d0e9']
            ]]
        ]);
        if (isset($chunkData['result']) && is_array($chunkData['result'])) {
            foreach ($chunkData['result'] as $log) {
                if (count($log['topics']) < 3) continue;
                $token0 = '0x' . substr($log['topics'][1], -40);
                $token1 = '0x' . substr($log['topics'][2], -40);
                $newToken = null;
                if (strtolower($token0) === $WETH || in_array(strtolower($token0), $STABLES)) {
                    $newToken = strtolower($token1);
                } elseif (strtolower($token1) === $WETH || in_array(strtolower($token1), $STABLES)) {
                    $newToken = strtolower($token0);
                }
                if ($newToken && !isset($ethTokens[$newToken])) {
                    $ethTokens[$newToken] = 'uniswapV2';
                }
            }
        }
        usleep(50000);
    }
    
    // Source 2: Alchemy Uniswap V3 PoolCreated
    for ($i = 0; $i < 50; $i++) {
        $chunkEnd = $latestBlock - ($i * 10);
        $chunkStart = $chunkEnd - 9;
        $chunkData = fetchJson($alchemyUrl, 4, [
            'id'=>1,'jsonrpc'=>'2.0','method'=>'eth_getLogs',
            'params'=>[[
                'fromBlock'=>'0x'.dechex($chunkStart),
                'toBlock'=>'0x'.dechex($chunkEnd),
                'address'=>'0x1F98431c8aD98523631AE4a59f267346ea31F984',
                'topics'=>['0x783cca1c0412dd0d695e784568c96da2e9c22ff989357a2e8b1d9b2b4e6b7118']
            ]]
        ]);
        if (isset($chunkData['result']) && is_array($chunkData['result'])) {
            foreach ($chunkData['result'] as $log) {
                if (count($log['topics']) < 3) continue;
                $token0 = '0x' . substr($log['topics'][1], -40);
                $token1 = '0x' . substr($log['topics'][2], -40);
                $newToken = null;
                if (strtolower($token0) === $WETH || in_array(strtolower($token0), $STABLES)) {
                    $newToken = strtolower($token1);
                } elseif (strtolower($token1) === $WETH || in_array(strtolower($token1), $STABLES)) {
                    $newToken = strtolower($token0);
                }
                if ($newToken && !isset($ethTokens[$newToken])) {
                    $ethTokens[$newToken] = 'uniswapV3';
                }
            }
        }
        usleep(50000);
    }
}

// Source 3: DexScreener
$profiles = fetchJson('https://api.dexscreener.com/token-profiles/latest/v1');
if (is_array($profiles)) {
    foreach ($profiles as $p) {
        if (($p['chainId']??'') !== 'ethereum') continue;
        $addr = strtolower($p['tokenAddress'] ?? '');
        if ($addr && !isset($ethTokens[$addr])) $ethTokens[$addr] = 'dexProfile';
    }
}

$boosts = fetchJson('https://api.dexscreener.com/token-boosts/latest/v1');
if (is_array($boosts)) {
    foreach ($boosts as $b) {
        if (($b['chainId']??'') !== 'ethereum') continue;
        $addr = strtolower($b['tokenAddress'] ?? '');
        if ($addr && !isset($ethTokens[$addr])) $ethTokens[$addr] = 'dexBoost';
    }
}

$topBoosts = fetchJson('https://api.dexscreener.com/token-boosts/top/v1');
if (is_array($topBoosts)) {
    foreach ($topBoosts as $b) {
        if (($b['chainId']??'') !== 'ethereum') continue;
        $addr = strtolower($b['tokenAddress'] ?? '');
        if ($addr && !isset($ethTokens[$addr])) $ethTokens[$addr] = 'dexTopBoost';
    }
}

// =========== STEP 2: Per-token details, safety, whales ===========
$coins = [];
$processed = 0;

foreach ($ethTokens as $tokenAddr => $source) {
    if (count($coins) >= 25) break;
    if ($processed >= 50) break;
    $processed++;
    
    // DexScreener data
    $pd = fetchJson("https://api.dexscreener.com/latest/dex/tokens/{$tokenAddr}", 4);
    if (!isset($pd['pairs'][0])) continue;
    $ethPairs = array_filter($pd['pairs'], fn($p) => ($p['chainId']??'') === 'ethereum');
    if (empty($ethPairs)) continue;
    usort($ethPairs, fn($a,$b) => floatval($b['liquidity']['usd']??0) <=> floatval($a['liquidity']['usd']??0));
    $pair = array_values($ethPairs)[0];
    
    $createdAt = intval($pair['pairCreatedAt'] ?? 0) / 1000;
    $ageHours = $createdAt > 0 ? round((time() - $createdAt) / 3600, 2) : 999;
    if ($ageHours > 72 || $ageHours < 0.05) continue;
    
    $liquidity = floatval($pair['liquidity']['usd'] ?? 0);
    $volume24h = floatval($pair['volume']['h24'] ?? 0);
    if ($liquidity < 2000) continue;
    
    $marketCap = floatval($pair['marketCap'] ?? 0);
    $price = floatval($pair['priceUsd'] ?? 0);
    $change1h = floatval($pair['priceChange']['h1'] ?? 0);
    
    // ============ GoPlus Safety ============
    $safetyChecks = [];
    $safetyScore = 0;
    $gpUrl = "https://api.gopluslabs.io/api/v1/token_security/1?contract_addresses={$tokenAddr}";
    $gpData = fetchJson($gpUrl, 4);
    $gp = $gpData['result'][strtolower($tokenAddr)] ?? null;
    
    if ($gp) {
        // 1. Liquidity check
        $liqPass = $liquidity >= 5000;
        $safetyChecks['liquidity'] = ['label'=>'Liq > $5K','pass'=>$liqPass,'value'=>'$'.number_format($liquidity/1000).'K'];
        if ($liqPass) $safetyScore++;
        
        // 2. LP locked or burned
        $lpHolders = $gp['lp_holders'] ?? [];
        $lpBurned = false;
        $lpLocked = false;
        foreach ($lpHolders as $lpH) {
            $lpAddr = strtolower($lpH['address'] ?? '');
            if (in_array($lpAddr, ['0x000000000000000000000000000000000000dead','0x0000000000000000000000000000000000000000'])) {
                $lpBurned = true;
            }
            if (($lpH['is_locked'] ?? 0) == 1) $lpLocked = true;
        }
        $lpSafe = $lpBurned || $lpLocked;
        $safetyChecks['lp'] = ['label'=>'LP Locked/Burned','pass'=>$lpSafe,'value'=>$lpBurned?'Burned':($lpLocked?'Locked':'NEITHER')];
        if ($lpSafe) $safetyScore++;
        
        // 3. Buy tax < 8%
        $buyTax = floatval($gp['buy_tax'] ?? 0) * 100;
        $btPass = $buyTax < 8;
        $safetyChecks['buy_tax'] = ['label'=>'Buy Tax < 8%','pass'=>$btPass,'value'=>round($buyTax,1).'%'];
        if ($btPass) $safetyScore++;
        
        // 4. Sell tax < 8%
        $sellTax = floatval($gp['sell_tax'] ?? 0) * 100;
        $stPass = $sellTax < 8;
        $safetyChecks['sell_tax'] = ['label'=>'Sell Tax < 8%','pass'=>$stPass,'value'=>round($sellTax,1).'%'];
        if ($stPass) $safetyScore++;
        
        // 5. No mint
        $canMint = ($gp['is_mintable'] ?? 0) == 1;
        $safetyChecks['mint'] = ['label'=>'No Mint Function','pass'=>!$canMint,'value'=>$canMint?'CAN MINT':'OK'];
        if (!$canMint) $safetyScore++;
        
        // 6. No blacklist
        $hasBlacklist = ($gp['can_take_back_ownership'] ?? 0) == 1 || ($gp['owner_change_balance'] ?? 0) == 1;
        $safetyChecks['blacklist'] = ['label'=>'No Blacklist','pass'=>!$hasBlacklist,'value'=>$hasBlacklist?'HAS':'OK'];
        if (!$hasBlacklist) $safetyScore++;
        
        // 7. Ownership renounced
        $ownerAddr = strtolower($gp['owner_address'] ?? '');
        $renounced = in_array($ownerAddr, ['','0x0000000000000000000000000000000000000000','0x000000000000000000000000000000000000dead']);
        $safetyChecks['owner'] = ['label'=>'Owner Renounced','pass'=>$renounced,'value'=>$renounced?'Renounced':substr($ownerAddr,0,8).'...'];
        if ($renounced) $safetyScore++;
        
        // 8. Top holder < 15%
        $holders = $gp['holders'] ?? [];
        $topPct = 0;
        if (!empty($holders)) $topPct = floatval($holders[0]['percent'] ?? 0) * 100;
        $topPass = $topPct < 15;
        $safetyChecks['top_holder'] = ['label'=>'Top Holder < 15%','pass'=>$topPass,'value'=>round($topPct,1).'%'];
        if ($topPass) $safetyScore++;
        
        // 9. Cannot sell - simulator
        $cannotSell = ($gp['cannot_sell_all'] ?? 0) == 1;
        $safetyChecks['sell_sim'] = ['label'=>'Sell Simulation OK','pass'=>!$cannotSell,'value'=>$cannotSell?'BLOCKED':'OK'];
        if (!$cannotSell) $safetyScore++;
        
        // 10. Buyer activity (proxy: 10+ buyers in 5min)
        $buys5m = intval($pair['txns']['m5']['buys'] ?? 0);
        $hasBuyers = $buys5m >= 10 || intval($pair['txns']['h1']['buys'] ?? 0) >= 30;
        $safetyChecks['buyers'] = ['label'=>'10+ Buyers / 5min','pass'=>$hasBuyers,'value'=>$buys5m.' / 5min'];
        if ($hasBuyers) $safetyScore++;
    } else {
        // No GoPlus data
        foreach (['liquidity','lp','buy_tax','sell_tax','mint','blacklist','owner','top_holder','sell_sim','buyers'] as $k) {
            $safetyChecks[$k] = ['label'=>ucfirst(str_replace('_',' ',$k)),'pass'=>false,'value'=>'NO DATA'];
        }
    }
    
    // Determine grade
    $grade = 'DANGER'; $safetyColor = '#FF4757';
    if ($safetyScore >= 9) { $grade = 'SAFE'; $safetyColor = '#00E676'; }
    elseif ($safetyScore >= 7) { $grade = 'OK'; $safetyColor = '#FFD32A'; }
    elseif ($safetyScore >= 5) { $grade = 'RISKY'; $safetyColor = '#FF9F43'; }
    
    // ============ Top Whales (Etherscan) ============
    $whales = [];
    $whaleUrl = "https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokentx&contractaddress={$tokenAddr}&page=1&offset=200&sort=desc&apikey={$ETHERSCAN_KEY}";
    $whaleData = fetchJson($whaleUrl, 5);
    if (isset($whaleData['result']) && is_array($whaleData['result'])) {
        $wallets = [];
        foreach ($whaleData['result'] as $tx) {
            $to = strtolower($tx['to'] ?? '');
            $from = strtolower($tx['from'] ?? '');
            $decimals = intval($tx['tokenDecimal'] ?? 18);
            $amount = floatval($tx['value'] ?? 0) / pow(10, $decimals);
            $timestamp = intval($tx['timeStamp'] ?? 0);
            
            foreach ([$to => 'buy', $from => 'sell'] as $wallet => $action) {
                if ($wallet === '0x0000000000000000000000000000000000000000') continue;
                if (!isset($wallets[$wallet])) {
                    $wallets[$wallet] = ['bought'=>0,'sold'=>0,'count_buys'=>0,'count_sells'=>0,'last_action_time'=>0,'last_action'=>''];
                }
                if ($action === 'buy') {
                    $wallets[$wallet]['bought'] += $amount;
                    $wallets[$wallet]['count_buys']++;
                } else {
                    $wallets[$wallet]['sold'] += $amount;
                    $wallets[$wallet]['count_sells']++;
                }
                if ($timestamp > $wallets[$wallet]['last_action_time']) {
                    $wallets[$wallet]['last_action_time'] = $timestamp;
                    $wallets[$wallet]['last_action'] = $action;
                }
            }
        }
        
        // Categorize whales
        $whaleList = [];
        foreach ($wallets as $w => $d) {
            $net = $d['bought'] - $d['sold'];
            $netUsd = abs($net) * $price;
            if ($netUsd < 1000) continue;
            
            $type = 'flipping';
            if ($d['bought'] > 0 && $d['sold'] / max($d['bought'], 0.001) < 0.2) $type = 'holding';
            elseif ($d['sold'] > $d['bought'] * 0.8) $type = 'dumping';
            
            $timeAgo = time() - $d['last_action_time'];
            $whaleList[] = [
                'wallet' => substr($w, 0, 6).'...'.substr($w, -4),
                'wallet_full' => $w,
                'type' => $type,
                'net_amount' => $net,
                'net_usd' => $netUsd,
                'net_usd_formatted' => $netUsd >= 1e6 ? '$'.number_format($netUsd/1e6,2).'M' : '$'.number_format($netUsd/1e3,1).'K',
                'count_buys' => $d['count_buys'],
                'count_sells' => $d['count_sells'],
                'time_display' => $timeAgo < 60 ? $timeAgo.'s' : ($timeAgo < 3600 ? floor($timeAgo/60).'m' : ($timeAgo < 86400 ? floor($timeAgo/3600).'h' : floor($timeAgo/86400).'d')),
                'explorer_url' => "https://etherscan.io/address/{$w}",
            ];
        }
        usort($whaleList, fn($a,$b) => $b['net_usd'] <=> $a['net_usd']);
        $whales = array_slice($whaleList, 0, 10);
    }
    
    $holdersCount = count(array_filter($whales, fn($w) => $w['type'] === 'holding'));
    $dumpersCount = count(array_filter($whales, fn($w) => $w['type'] === 'dumping'));
    
    $coins[] = [
        'symbol' => $pair['baseToken']['symbol'] ?? 'TOKEN',
        'name' => $pair['baseToken']['name'] ?? '',
        'address' => $tokenAddr,
        'source' => $source,
        'age_hours' => $ageHours,
        'age_display' => $ageHours < 1 ? round($ageHours * 60) . 'm' : round($ageHours, 1) . 'h',
        'price' => $price,
        'price_formatted' => $price >= 1 ? '$'.number_format($price,4) : '$'.number_format($price,9),
        'change_1h' => round($change1h, 1),
        'mc_formatted' => $marketCap >= 1e6 ? '$'.number_format($marketCap/1e6,1).'M' : '$'.number_format($marketCap/1e3,0).'K',
        'liquidity_formatted' => '$'.number_format($liquidity/1e3,0).'K',
        'volume_formatted' => $volume24h >= 1e6 ? '$'.number_format($volume24h/1e6,2).'M' : '$'.number_format($volume24h/1e3,0).'K',
        'safety_score' => $safetyScore,
        'safety_grade' => $grade,
        'safety_color' => $safetyColor,
        'safety_checks' => $safetyChecks,
        'total_whales' => count($whales),
        'holders_count' => $holdersCount,
        'dumpers_count' => $dumpersCount,
        'whales' => $whales,
        'dex_url' => $pair['url'] ?? "https://dexscreener.com/ethereum/{$tokenAddr}",
    ];
    
    usleep(100000);
}

// Sort by liquidity (biggest first)
usort($coins, fn($a, $b) => $b['safety_score'] - $a['safety_score']);

$result = [
    'success' => true,
    'updated_at' => date('Y-m-d H:i:s'),
    'stats' => [
        'sources_found' => count($ethTokens),
        'total_coins' => count($coins),
        'safe_coins' => count(array_filter($coins, fn($c) => $c['safety_grade'] === 'SAFE')),
        'risky_coins' => count(array_filter($coins, fn($c) => in_array($c['safety_grade'], ['RISKY','DANGER']))),
        'total_whales' => array_sum(array_column($coins, 'total_whales')),
    ],
    'coins' => $coins,
];

file_put_contents($cacheFile, json_encode($result));
echo json_encode($result);
