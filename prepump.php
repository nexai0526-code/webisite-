<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$cacheFile = __DIR__ . '/data/prepump_cache.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 90) {
    echo file_get_contents($cacheFile);
    exit;
}

function fetchJson($url, $timeout = 6) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NexAI/1.0');
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// Load smart wallets for matching
$smartWalletsFile = __DIR__ . '/data/eth_smart_wallets.json';
$smartWallets = [];
if (file_exists($smartWalletsFile)) {
    $smartWallets = json_decode(file_get_contents($smartWalletsFile), true) ?: [];
}
$smartAddrs = array_keys($smartWallets);

// Step 1: Get ETH tokens
$ethTokens = [];

$profiles = fetchJson('https://api.dexscreener.com/token-profiles/latest/v1');
if (is_array($profiles)) {
    foreach ($profiles as $p) {
        if (($p['chainId']??'') !== 'ethereum') continue;
        $addr = strtolower($p['tokenAddress'] ?? '');
        if ($addr) $ethTokens[$addr] = true;
        if (count($ethTokens) >= 25) break;
    }
}

// Also pull trending from search
$searchData = fetchJson('https://api.dexscreener.com/latest/dex/search?q=WETH');
if (isset($searchData['pairs'])) {
    foreach ($searchData['pairs'] as $p) {
        if (($p['chainId']??'') !== 'ethereum') continue;
        $createdAt = intval($p['pairCreatedAt'] ?? 0) / 1000;
        $ageHours = $createdAt > 0 ? (time() - $createdAt) / 3600 : 999;
        if ($ageHours > 48) continue;
        $addr = strtolower($p['baseToken']['address'] ?? '');
        if ($addr) $ethTokens[$addr] = true;
        if (count($ethTokens) >= 40) break;
    }
}

$candidates = [];

foreach (array_keys($ethTokens) as $tokenAddr) {
    if (count($candidates) >= 20) break;
    
    $pd = fetchJson("https://api.dexscreener.com/latest/dex/tokens/{$tokenAddr}", 4);
    if (!isset($pd['pairs'][0])) continue;
    
    $ethPairs = array_filter($pd['pairs'], fn($p) => ($p['chainId']??'') === 'ethereum');
    if (empty($ethPairs)) continue;
    usort($ethPairs, fn($a,$b) => floatval($b['liquidity']['usd']??0) <=> floatval($a['liquidity']['usd']??0));
    $pair = array_values($ethPairs)[0];
    
    $createdAt = intval($pair['pairCreatedAt'] ?? 0) / 1000;
    $ageHours = $createdAt > 0 ? round((time() - $createdAt) / 3600, 2) : 999;
    if ($ageHours > 48 || $ageHours < 0.25) continue; // 15min - 48h
    
    $volume24h = floatval($pair['volume']['h24'] ?? 0);
    $volume1h = floatval($pair['volume']['h1'] ?? 0);
    $volume5m = floatval($pair['volume']['m5'] ?? 0);
    $liquidity = floatval($pair['liquidity']['usd'] ?? 0);
    
    if ($liquidity < 5000 || $volume24h < 5000) continue;
    
    $buys5m = intval($pair['txns']['m5']['buys'] ?? 0);
    $sells5m = intval($pair['txns']['m5']['sells'] ?? 0);
    $buys1h = intval($pair['txns']['h1']['buys'] ?? 0);
    $sells1h = intval($pair['txns']['h1']['sells'] ?? 0);
    $buys24h = intval($pair['txns']['h24']['buys'] ?? 0);
    $sells24h = intval($pair['txns']['h24']['sells'] ?? 0);
    
    $change5m = floatval($pair['priceChange']['m5'] ?? 0);
    $change1h = floatval($pair['priceChange']['h1'] ?? 0);
    $change24h = floatval($pair['priceChange']['h24'] ?? 0);
    
    // ============ PRE-PUMP SCORING ============
    $score = 0;
    $signals = [];
    
    // 1. Volume spike: 5m × 12 vs 1h avg
    $expectedVol5m = $volume1h / 12;
    $volRatio = $expectedVol5m > 0 ? $volume5m / $expectedVol5m : 0;
    if ($volRatio >= 3) { $score += 20; $signals[] = ['icon'=>'🚀','text'=>'Volume +'.round(($volRatio-1)*100).'% (5m vs avg)','strong'=>true]; }
    elseif ($volRatio >= 2) { $score += 12; $signals[] = ['icon'=>'📈','text'=>'Volume rising '.round(($volRatio-1)*100).'%','strong'=>false]; }
    elseif ($volRatio >= 1.3) { $score += 5; $signals[] = ['icon'=>'📊','text'=>'Volume picking up','strong'=>false]; }
    
    // 2. Buy/Sell ratio (5m)
    $totalTx5m = $buys5m + $sells5m;
    $buyRatio5m = $totalTx5m > 0 ? ($buys5m / $totalTx5m) * 100 : 0;
    if ($totalTx5m >= 10 && $buyRatio5m >= 75) { $score += 20; $signals[] = ['icon'=>'🟢','text'=>'Heavy buying '.round($buyRatio5m).'% ('.$buys5m.'B/'.$sells5m.'S)','strong'=>true]; }
    elseif ($totalTx5m >= 5 && $buyRatio5m >= 65) { $score += 12; $signals[] = ['icon'=>'🟢','text'=>'Strong buying '.round($buyRatio5m).'%','strong'=>false]; }
    elseif ($totalTx5m >= 3 && $buyRatio5m >= 55) { $score += 5; $signals[] = ['icon'=>'🟢','text'=>'More buys than sells','strong'=>false]; }
    
    // 3. Buyer activity surge (5m vs 1h)
    $expectedBuys5m = $buys1h / 12;
    $buyVelocity = $expectedBuys5m > 0 ? $buys5m / $expectedBuys5m : 0;
    if ($buyVelocity >= 3 && $buys5m >= 10) { $score += 15; $signals[] = ['icon'=>'⚡','text'=>'Buyer surge '.$buys5m.' in 5min','strong'=>true]; }
    elseif ($buyVelocity >= 2 && $buys5m >= 5) { $score += 8; $signals[] = ['icon'=>'⚡','text'=>'Buyers accelerating','strong'=>false]; }
    
    // 4. Age sweet spot (1-12h ideal)
    if ($ageHours >= 1 && $ageHours <= 12) { $score += 10; $signals[] = ['icon'=>'🎯','text'=>'Sweet spot age ('.($ageHours<1?round($ageHours*60).'m':round($ageHours,1).'h').')','strong'=>false]; }
    elseif ($ageHours >= 0.5 && $ageHours <= 24) { $score += 5; }
    
    // 5. Price coiling (low 5m change but rising buyers)
    if (abs($change5m) < 3 && $buys5m >= 10 && $buyRatio5m >= 60) {
        $score += 15;
        $signals[] = ['icon'=>'🌀','text'=>'Price coiling — breakout imminent','strong'=>true];
    }
    
    // 6. 1h momentum building
    if ($change1h >= 5 && $change1h <= 30 && $change5m >= 0) {
        $score += 10;
        $signals[] = ['icon'=>'📈','text'=>'1h momentum '.round($change1h,1).'%','strong'=>false];
    } elseif ($change1h >= 30) {
        // Already pumping - lower score (too late)
        $score += 5;
        $signals[] = ['icon'=>'🔥','text'=>'Already pumping +'.round($change1h).'%','strong'=>false];
    }
    
    // 7. Smart money detection - check if any known smart wallets bought this token
    $smartMatched = 0;
    $matchedLabels = [];
    if (!empty($smartAddrs)) {
        // Quick check via Etherscan - last 20 token transfers
        $checkUrl = "https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokentx&contractaddress={$tokenAddr}&page=1&offset=20&sort=desc&apikey=TXMMKSSBMMYF1FF7DGNRHE4ASW2WIKWS43";
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
    if ($smartMatched >= 2) { $score += 20; $signals[] = ['icon'=>'🧠','text'=>$smartMatched.' SMART MONEY entered: '.implode(', ',array_unique($matchedLabels)),'strong'=>true]; }
    elseif ($smartMatched === 1) { $score += 10; $signals[] = ['icon'=>'🧠','text'=>'1 smart wallet entered: '.implode(',',$matchedLabels),'strong'=>false]; }
    
    // 8. Liquidity health (not draining)
    if ($liquidity >= 50000) { $score += 5; $signals[] = ['icon'=>'💧','text'=>'Strong liquidity $'.round($liquidity/1000).'K','strong'=>false]; }
    elseif ($liquidity >= 20000) { $score += 3; }
    
    // ============ FILTER: Need decent score and signals ============
    if ($score < 30) continue;
    if (empty($signals)) continue;
    
    // Determine grade
    $grade = 'WATCH';
    $gradeColor = '#9B9BB0';
    $probability = '40-50%';
    if ($score >= 80) { $grade = 'PUMP IMMINENT'; $gradeColor = '#FF4757'; $probability = '90%+'; }
    elseif ($score >= 65) { $grade = 'HIGH ALERT'; $gradeColor = '#FF9F43'; $probability = '70-85%'; }
    elseif ($score >= 50) { $grade = 'STRONG SIGNAL'; $gradeColor = '#FFD32A'; $probability = '55-70%'; }
    elseif ($score >= 40) { $grade = 'EARLY SIGNAL'; $gradeColor = '#00D2FF'; $probability = '40-55%'; }
    
    $candidates[] = [
        'symbol' => $pair['baseToken']['symbol'] ?? 'TOKEN',
        'name' => $pair['baseToken']['name'] ?? '',
        'address' => $tokenAddr,
        'age_hours' => $ageHours,
        'age_display' => $ageHours < 1 ? round($ageHours * 60) . 'm' : round($ageHours, 1) . 'h',
        'price' => floatval($pair['priceUsd'] ?? 0),
        'price_formatted' => floatval($pair['priceUsd']??0) >= 1 ? '$'.number_format(floatval($pair['priceUsd']),4) : '$'.number_format(floatval($pair['priceUsd']),9),
        'change_5m' => round($change5m, 1),
        'change_1h' => round($change1h, 1),
        'change_24h' => round($change24h, 1),
        'volume_5m' => $volume5m,
        'volume_1h' => $volume1h,
        'volume_24h' => $volume24h,
        'volume_formatted' => $volume24h >= 1e6 ? '$'.number_format($volume24h/1e6,2).'M' : '$'.number_format($volume24h/1e3,0).'K',
        'liquidity' => $liquidity,
        'liquidity_formatted' => '$'.number_format($liquidity/1e3,0).'K',
        'market_cap' => floatval($pair['marketCap'] ?? 0),
        'mc_formatted' => floatval($pair['marketCap']??0) >= 1e6 ? '$'.number_format(floatval($pair['marketCap'])/1e6,1).'M' : '$'.number_format(floatval($pair['marketCap']??0)/1e3,0).'K',
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
        'dex_url' => $pair['url'] ?? '',
    ];
    
    usleep(80000);
}

// Sort by score (highest = most imminent)
usort($candidates, fn($a, $b) => $b['score'] - $a['score']);

$result = [
    'success' => true,
    'updated_at' => date('Y-m-d H:i:s'),
    'stats' => [
        'total_scanned' => count($ethTokens),
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
