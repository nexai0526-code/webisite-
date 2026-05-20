<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ALCHEMY_KEY = 'Pzh6P5a3Bjb69lFfn8dx0';
$ETHERSCAN_KEY = 'TXMMKSSBMMYF1FF7DGNRHE4ASW2WIKWS43';
$cacheFile = __DIR__ . '/data/prepump_cache.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 90) {
    echo file_get_contents($cacheFile);
    exit;
}

function fetchJson($url, $timeout = 6, $postData = null) {
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

// Load smart wallets
$smartWalletsFile = __DIR__ . '/data/eth_smart_wallets.json';
$smartWallets = [];
if (file_exists($smartWalletsFile)) {
    $smartWallets = json_decode(file_get_contents($smartWalletsFile), true) ?: [];
}
$smartAddrs = array_keys($smartWallets);

// ============ STEP 1: Get recent Uniswap V2 PairCreated events ============
// Uniswap V2 Factory: 0x5C69bEe701ef814a2B6a3EDD4B1652CB9cc5aA6f
// PairCreated event topic: 0x0d3648bd0f6ba80134a33ba9275ac585d9d315f0ad8355cddefde31afa28d0e9

$alchemyUrl = "https://eth-mainnet.g.alchemy.com/v2/{$ALCHEMY_KEY}";

// Get latest block
$blockData = fetchJson($alchemyUrl, 5, [
    'id' => 1,
    'jsonrpc' => '2.0',
    'method' => 'eth_blockNumber'
]);
$latestBlock = isset($blockData['result']) ? hexdec($blockData['result']) : 0;
$fromBlock = $latestBlock - 1000; // ~3 hours back

// Fetch Uniswap V2 PairCreated events
$logsV2 = fetchJson($alchemyUrl, 8, [
    'id' => 1,
    'jsonrpc' => '2.0',
    'method' => 'eth_getLogs',
    'params' => [[
        'fromBlock' => '0x' . dechex($fromBlock),
        'toBlock' => 'latest',
        'address' => '0x5C69bEe701ef814a2B6a3EDD4B1652CB9cc5aA6f',
        'topics' => ['0x0d3648bd0f6ba80134a33ba9275ac585d9d315f0ad8355cddefde31afa28d0e9']
    ]]
]);

// Also Uniswap V3 PairCreated (PoolCreated): 0x783cca1c0412dd0d695e784568c96da2e9c22ff989357a2e8b1d9b2b4e6b7118
$logsV3 = fetchJson($alchemyUrl, 8, [
    'id' => 1,
    'jsonrpc' => '2.0',
    'method' => 'eth_getLogs',
    'params' => [[
        'fromBlock' => '0x' . dechex($fromBlock),
        'toBlock' => 'latest',
        'address' => '0x1F98431c8aD98523631AE4a59f267346ea31F984',
        'topics' => ['0x783cca1c0412dd0d695e784568c96da2e9c22ff989357a2e8b1d9b2b4e6b7118']
    ]]
]);

$WETH = '0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2';
$STABLECOINS = [
    '0xdac17f958d2ee523a2206206994597c13d831ec7', // USDT
    '0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48', // USDC
    '0x6b175474e89094c44da98b954eedeac495271d0f', // DAI
];

$newTokens = [];

// Parse V2 logs
if (isset($logsV2['result']) && is_array($logsV2['result'])) {
    foreach ($logsV2['result'] as $log) {
        if (count($log['topics']) < 3) continue;
        $token0 = '0x' . substr($log['topics'][1], -40);
        $token1 = '0x' . substr($log['topics'][2], -40);
        $newToken = null;
        if (strtolower($token0) === $WETH || in_array(strtolower($token0), $STABLECOINS)) {
            $newToken = $token1;
        } elseif (strtolower($token1) === $WETH || in_array(strtolower($token1), $STABLECOINS)) {
            $newToken = $token0;
        }
        if ($newToken && !isset($newTokens[strtolower($newToken)])) {
            $newTokens[strtolower($newToken)] = ['source' => 'uniswapV2', 'block' => hexdec($log['blockNumber'])];
        }
    }
}

// Parse V3 logs
if (isset($logsV3['result']) && is_array($logsV3['result'])) {
    foreach ($logsV3['result'] as $log) {
        if (count($log['topics']) < 3) continue;
        $token0 = '0x' . substr($log['topics'][1], -40);
        $token1 = '0x' . substr($log['topics'][2], -40);
        $newToken = null;
        if (strtolower($token0) === $WETH || in_array(strtolower($token0), $STABLECOINS)) {
            $newToken = $token1;
        } elseif (strtolower($token1) === $WETH || in_array(strtolower($token1), $STABLECOINS)) {
            $newToken = $token0;
        }
        if ($newToken && !isset($newTokens[strtolower($newToken)])) {
            $newTokens[strtolower($newToken)] = ['source' => 'uniswapV3', 'block' => hexdec($log['blockNumber'])];
        }
    }
}

// Also add DexScreener trending/profiles as backup
$boosts = fetchJson('https://api.dexscreener.com/token-boosts/latest/v1');
if (is_array($boosts)) {
    foreach ($boosts as $b) {
        if (($b['chainId']??'') !== 'ethereum') continue;
        $addr = strtolower($b['tokenAddress'] ?? '');
        if ($addr && !isset($newTokens[$addr])) $newTokens[$addr] = ['source' => 'dexBoost', 'block' => 0];
    }
}

$profiles = fetchJson('https://api.dexscreener.com/token-profiles/latest/v1');
if (is_array($profiles)) {
    foreach ($profiles as $p) {
        if (($p['chainId']??'') !== 'ethereum') continue;
        $addr = strtolower($p['tokenAddress'] ?? '');
        if ($addr && !isset($newTokens[$addr])) $newTokens[$addr] = ['source' => 'dexProfile', 'block' => 0];
    }
}

// ============ STEP 2: Get details for each token + score ============
$candidates = [];
$processed = 0;

foreach ($newTokens as $tokenAddr => $info) {
    if ($processed >= 30) break;
    $processed++;
    
    $pd = fetchJson("https://api.dexscreener.com/latest/dex/tokens/{$tokenAddr}", 4);
    if (!isset($pd['pairs'][0])) continue;
    
    $ethPairs = array_filter($pd['pairs'], fn($p) => ($p['chainId']??'') === 'ethereum');
    if (empty($ethPairs)) continue;
    usort($ethPairs, fn($a,$b) => floatval($b['liquidity']['usd']??0) <=> floatval($a['liquidity']['usd']??0));
    $pair = array_values($ethPairs)[0];
    
    $createdAt = intval($pair['pairCreatedAt'] ?? 0) / 1000;
    $ageHours = $createdAt > 0 ? round((time() - $createdAt) / 3600, 2) : 999;
    if ($ageHours > 48 || $ageHours < 0.05) continue;
    
    $volume24h = floatval($pair['volume']['h24'] ?? 0);
    $volume1h = floatval($pair['volume']['h1'] ?? 0);
    $volume5m = floatval($pair['volume']['m5'] ?? 0);
    $liquidity = floatval($pair['liquidity']['usd'] ?? 0);
    if ($liquidity < 2000 || $volume24h < 1000) continue;
    
    $buys5m = intval($pair['txns']['m5']['buys'] ?? 0);
    $sells5m = intval($pair['txns']['m5']['sells'] ?? 0);
    $buys1h = intval($pair['txns']['h1']['buys'] ?? 0);
    $sells1h = intval($pair['txns']['h1']['sells'] ?? 0);
    $buys24h = intval($pair['txns']['h24']['buys'] ?? 0);
    $sells24h = intval($pair['txns']['h24']['sells'] ?? 0);
    $change5m = floatval($pair['priceChange']['m5'] ?? 0);
    $change1h = floatval($pair['priceChange']['h1'] ?? 0);
    $change24h = floatval($pair['priceChange']['h24'] ?? 0);
    $marketCap = floatval($pair['marketCap'] ?? 0);
    $price = floatval($pair['priceUsd'] ?? 0);
    
    $score = 0;
    $signals = [];
    
    // Bonus for fresh from Uniswap event
    if (in_array($info['source'], ['uniswapV2', 'uniswapV3'])) {
        $score += 10;
        $signals[] = ['icon'=>'🆕','text'=>'Fresh '.$info['source'].' launch','strong'=>true];
    }
    
    // Volume spike
    $expVol5m = $volume1h / 12;
    $volRatio = $expVol5m > 0 ? $volume5m / $expVol5m : 0;
    if ($volRatio >= 3) { $score += 20; $signals[] = ['icon'=>'🚀','text'=>'Volume +'.round(($volRatio-1)*100).'% spike','strong'=>true]; }
    elseif ($volRatio >= 2) { $score += 12; $signals[] = ['icon'=>'📈','text'=>'Volume rising '.round(($volRatio-1)*100).'%','strong'=>false]; }
    elseif ($volRatio >= 1.3) { $score += 5; }
    
    // Buy/Sell ratio 5m
    $totalTx5m = $buys5m + $sells5m;
    $buyRatio5m = $totalTx5m > 0 ? ($buys5m / $totalTx5m) * 100 : 0;
    if ($totalTx5m >= 10 && $buyRatio5m >= 75) { $score += 20; $signals[] = ['icon'=>'🟢','text'=>'Heavy buying '.round($buyRatio5m).'% ('.$buys5m.'B/'.$sells5m.'S)','strong'=>true]; }
    elseif ($totalTx5m >= 5 && $buyRatio5m >= 65) { $score += 12; $signals[] = ['icon'=>'🟢','text'=>'Strong buying '.round($buyRatio5m).'%','strong'=>false]; }
    elseif ($totalTx5m >= 3 && $buyRatio5m >= 55) { $score += 5; }
    
    // Buyer velocity
    $expBuys5m = $buys1h / 12;
    $buyVel = $expBuys5m > 0 ? $buys5m / $expBuys5m : 0;
    if ($buyVel >= 3 && $buys5m >= 10) { $score += 15; $signals[] = ['icon'=>'⚡','text'=>$buys5m.' buyers in 5min surge','strong'=>true]; }
    elseif ($buyVel >= 2 && $buys5m >= 5) { $score += 8; }
    
    // Age sweet spot
    if ($ageHours >= 0.5 && $ageHours <= 8) { $score += 10; $signals[] = ['icon'=>'🎯','text'=>'Sweet spot age ('.($ageHours<1?round($ageHours*60).'m':round($ageHours,1).'h').')','strong'=>false]; }
    elseif ($ageHours >= 0.1 && $ageHours <= 24) { $score += 5; }
    
    // Price coiling
    if (abs($change5m) < 3 && $buys5m >= 10 && $buyRatio5m >= 60) {
        $score += 15;
        $signals[] = ['icon'=>'🌀','text'=>'Coiling - breakout imminent','strong'=>true];
    }
    
    // 1h momentum
    if ($change1h >= 5 && $change1h <= 50 && $change5m >= 0) {
        $score += 10;
        $signals[] = ['icon'=>'📈','text'=>'Building momentum '.round($change1h,1).'%','strong'=>false];
    } elseif ($change1h >= 100) {
        $score += 5;
        $signals[] = ['icon'=>'🔥','text'=>'Already pumping +'.round($change1h).'%','strong'=>false];
    }
    
    // Smart money check
    $smartMatched = 0;
    $matchedLabels = [];
    if (!empty($smartAddrs) && $info['source'] !== 'dexProfile') {
        $checkUrl = "https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokentx&contractaddress={$tokenAddr}&page=1&offset=30&sort=desc&apikey={$ETHERSCAN_KEY}";
        $checkData = fetchJson($checkUrl, 4);
        if (isset($checkData['result']) && is_array($checkData['result'])) {
            foreach ($checkData['result'] as $tx) {
                $to = strtolower($tx['to'] ?? '');
                if (in_array($to, $smartAddrs)) {
                    $smartMatched++;
                    if (isset($smartWallets[$to]['discovered_from'])) {
                        $matchedLabels[] = $smartWallets[$to]['discovered_from'];
                    }
                }
            }
        }
    }
    if ($smartMatched >= 2) { $score += 20; $signals[] = ['icon'=>'🧠','text'=>$smartMatched.' SMART MONEY: '.implode(', ',array_unique($matchedLabels)),'strong'=>true]; }
    elseif ($smartMatched === 1) { $score += 10; $signals[] = ['icon'=>'🧠','text'=>'1 smart wallet: '.implode(',',$matchedLabels),'strong'=>false]; }
    
    // Liquidity health
    if ($liquidity >= 50000) { $score += 5; $signals[] = ['icon'=>'💧','text'=>'Strong liq '.number_format($liquidity/1000).'K','strong'=>false]; }
    elseif ($liquidity >= 20000) { $score += 3; }
    
    // MC/Liq sweet spot
    $mcLiqRatio = $liquidity > 0 ? $marketCap / $liquidity : 0;
    if ($mcLiqRatio >= 5 && $mcLiqRatio <= 15) { $score += 8; $signals[] = ['icon'=>'⚖️','text'=>'Healthy MC/Liq '.round($mcLiqRatio,1).'x','strong'=>false]; }
    elseif ($mcLiqRatio > 25) { $score -= 5; }
    
    // Total tx velocity
    $totalTx24h = $buys24h + $sells24h;
    if ($totalTx24h >= 500) { $score += 10; $signals[] = ['icon'=>'💥','text'=>$totalTx24h.' txs 24h high attention','strong'=>true]; }
    elseif ($totalTx24h >= 200) { $score += 5; }
    
    // Vol/MC ratio
    $volMcRatio = $marketCap > 0 ? $volume24h / $marketCap : 0;
    if ($volMcRatio >= 2) { $score += 8; $signals[] = ['icon'=>'🔄','text'=>'High turnover '.round($volMcRatio,1).'x','strong'=>true]; }
    elseif ($volMcRatio >= 1) { $score += 4; }
    
    if ($score < 25) continue;
    if (empty($signals)) continue;
    
    $grade = 'WATCH';
    $gradeColor = '#9B9BB0';
    $probability = '40-50%';
    if ($score >= 80) { $grade = 'PUMP IMMINENT'; $gradeColor = '#FF4757'; $probability = '90%+'; }
    elseif ($score >= 65) { $grade = 'HIGH ALERT'; $gradeColor = '#FF9F43'; $probability = '70-85%'; }
    elseif ($score >= 50) { $grade = 'STRONG SIGNAL'; $gradeColor = '#FFD32A'; $probability = '55-70%'; }
    elseif ($score >= 35) { $grade = 'EARLY SIGNAL'; $gradeColor = '#00D2FF'; $probability = '40-55%'; }
    
    $candidates[] = [
        'symbol' => $pair['baseToken']['symbol'] ?? 'TOKEN',
        'name' => $pair['baseToken']['name'] ?? '',
        'address' => $tokenAddr,
        'source' => $info['source'],
        'age_hours' => $ageHours,
        'age_display' => $ageHours < 1 ? round($ageHours * 60) . 'm' : round($ageHours, 1) . 'h',
        'price' => $price,
        'price_formatted' => $price >= 1 ? '$'.number_format($price,4) : '$'.number_format($price,9),
        'change_5m' => round($change5m, 1),
        'change_1h' => round($change1h, 1),
        'change_24h' => round($change24h, 1),
        'volume_5m' => $volume5m,
        'volume_1h' => $volume1h,
        'volume_24h' => $volume24h,
        'volume_formatted' => $volume24h >= 1e6 ? '$'.number_format($volume24h/1e6,2).'M' : '$'.number_format($volume24h/1e3,0).'K',
        'liquidity' => $liquidity,
        'liquidity_formatted' => '$'.number_format($liquidity/1e3,0).'K',
        'market_cap' => $marketCap,
        'mc_formatted' => $marketCap >= 1e6 ? '$'.number_format($marketCap/1e6,1).'M' : '$'.number_format($marketCap/1e3,0).'K',
        'buys_5m' => $buys5m,
        'sells_5m' => $sells5m,
        'buys_1h' => $buys1h,
        'sells_1h' => $sells1h,
        'buy_ratio_5m' => round($buyRatio5m),
        'vol_spike_ratio' => round($volRatio, 2),
        'smart_money_count' => $smartMatched,
        'score' => $score,
        'grade' => $grade,
        'grade_color' => $gradeColor,
        'probability' => $probability,
        'signals' => $signals,
        'dex_url' => $pair['url'] ?? "https://dexscreener.com/ethereum/{$tokenAddr}",
    ];
    
    usleep(50000);
}

usort($candidates, fn($a, $b) => $b['score'] - $a['score']);

$result = [
    'success' => true,
    'updated_at' => date('Y-m-d H:i:s'),
    'block_scanned' => $latestBlock,
    'stats' => [
        'total_sources' => count($newTokens),
        'total_scanned' => $processed,
        'total_candidates' => count($candidates),
        'pump_imminent' => count(array_filter($candidates, fn($c) => $c['grade'] === 'PUMP IMMINENT')),
        'high_alert' => count(array_filter($candidates, fn($c) => $c['grade'] === 'HIGH ALERT')),
        'strong_signal' => count(array_filter($candidates, fn($c) => $c['grade'] === 'STRONG SIGNAL')),
        'early_signal' => count(array_filter($candidates, fn($c) => $c['grade'] === 'EARLY SIGNAL')),
    ],
    'coins' => $candidates,
];

file_put_contents($cacheFile, json_encode($result));
echo json_encode($result);
