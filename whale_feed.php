<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ETHERSCAN_KEY = 'TXMMKSSBMMYF1FF7DGNRHE4ASW2WIKWS43';
$ALCHEMY_KEY = 'Pzh6P5a3Bjb69lFfn8dx0';

$cacheFile = __DIR__ . '/data/whale_feed.json';
$lastSeenFile = __DIR__ . '/data/whale_last_seen.json';
$walletsFile = __DIR__ . '/data/eth_smart_wallets.json';

if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);

// Serve cached feed if recent
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 120) {
    echo file_get_contents($cacheFile);
    exit;
}

function fetchJson($url, $timeout = 5) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NexAI/1.0');
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// Load whales
if (!file_exists($walletsFile)) {
    echo json_encode(['success'=>false,'error'=>'No whales seeded']);
    exit;
}
$whales = json_decode(file_get_contents($walletsFile), true);

// Load last seen tx hashes (to detect NEW activity)
$lastSeen = file_exists($lastSeenFile) ? json_decode(file_get_contents($lastSeenFile), true) : [];
if (!is_array($lastSeen)) $lastSeen = [];

// Load existing feed (latest 100 alerts)
$existingFeed = [];
if (file_exists($cacheFile)) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    $existingFeed = $cached['alerts'] ?? [];
}

$newAlerts = [];
$STABLECOINS = ['USDT','USDC','DAI','BUSD','WETH','ETH'];

// Process whales in batches (rate limit safety)
$walletAddrs = array_keys($whales);
$batchSize = 25; // Process 25 per call (we'll cycle through)

// Determine which batch to process (round-robin)
$batchIndex = intval($_GET['batch'] ?? 0);
if ($batchIndex >= ceil(count($walletAddrs) / $batchSize)) $batchIndex = 0;

$start = $batchIndex * $batchSize;
$batchWallets = array_slice($walletAddrs, $start, $batchSize);

$processed = 0;
$apiCalls = 0;

foreach ($batchWallets as $addr) {
    $whale = $whales[$addr];
    $url = "https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokentx&address={$addr}&page=1&offset=5&sort=desc&apikey={$ETHERSCAN_KEY}";
    $data = fetchJson($url, 4);
    $apiCalls++;
    
    if (!isset($data['result']) || !is_array($data['result'])) continue;
    $processed++;
    
    $lastTxHash = $lastSeen[$addr] ?? '';
    $newestHash = '';
    
    foreach ($data['result'] as $tx) {
        $hash = $tx['hash'] ?? '';
        if (!$newestHash) $newestHash = $hash;
        
        // Stop if we've seen this transaction before
        if ($hash === $lastTxHash) break;
        
        $timestamp = intval($tx['timeStamp'] ?? 0);
        $timeAgo = time() - $timestamp;
        if ($timeAgo > 86400) continue; // Only last 24h
        
        $from = strtolower($tx['from'] ?? '');
        $to = strtolower($tx['to'] ?? '');
        $symbol = $tx['tokenSymbol'] ?? 'UNKNOWN';
        $name = $tx['tokenName'] ?? '';
        $contract = strtolower($tx['contractAddress'] ?? '');
        $decimals = intval($tx['tokenDecimal'] ?? 18);
        $amount = floatval($tx['value'] ?? 0) / pow(10, $decimals);
        if ($amount < 0.001) continue;
        
        // Skip stablecoins (not interesting)
        if (in_array(strtoupper($symbol), $STABLECOINS)) continue;
        
        $action = $to === $addr ? 'buy' : ($from === $addr ? 'sell' : null);
        if (!$action) continue;
        
        $alertId = $hash . '_' . $addr;
        
        $newAlerts[] = [
            'id' => $alertId,
            'wallet' => substr($addr, 0, 6) . '...' . substr($addr, -4),
            'wallet_full' => $addr,
            'whale_label' => $whale['discovered_from'] ?? 'unknown',
            'whale_type' => $whale['type'] ?? 'trader',
            'whale_score' => $whale['score'] ?? 50,
            'action' => $action,
            'symbol' => $symbol,
            'name' => $name,
            'contract' => $contract,
            'amount' => $amount,
            'amount_formatted' => $amount >= 1e6 ? number_format($amount/1e6,2).'M' : ($amount >= 1e3 ? number_format($amount/1e3,1).'K' : number_format($amount,2)),
            'timestamp' => $timestamp,
            'time_ago' => $timeAgo,
            'time_display' => $timeAgo < 60 ? $timeAgo.'s' : ($timeAgo < 3600 ? floor($timeAgo/60).'m' : ($timeAgo < 86400 ? floor($timeAgo/3600).'h' : floor($timeAgo/86400).'d')),
            'tx_hash' => $hash,
            'explorer_url' => "https://etherscan.io/tx/{$hash}",
            'dex_url' => "https://dexscreener.com/ethereum/{$contract}",
            'wallet_url' => "https://etherscan.io/address/{$addr}",
        ];
    }
    
    if ($newestHash) $lastSeen[$addr] = $newestHash;
    usleep(150000); // Rate limit
    
    if ($apiCalls >= 25) break; // Safety limit
}

// Save last seen state
file_put_contents($lastSeenFile, json_encode($lastSeen));

// Get price/MC data for unique tokens to enrich alerts
$uniqueContracts = array_unique(array_column($newAlerts, 'contract'));
$priceData = [];
foreach (array_slice($uniqueContracts, 0, 15) as $contract) {
    if (empty($contract)) continue;
    $pd = fetchJson("https://api.dexscreener.com/latest/dex/tokens/{$contract}", 3);
    if (isset($pd['pairs'][0])) {
        $ethPairs = array_filter($pd['pairs'], fn($p) => ($p['chainId']??'') === 'ethereum');
        if (!empty($ethPairs)) {
            $pair = array_values($ethPairs)[0];
            $price = floatval($pair['priceUsd'] ?? 0);
            $priceData[$contract] = [
                'price' => $price,
                'mc' => floatval($pair['marketCap'] ?? 0),
                'change_1h' => round(floatval($pair['priceChange']['h1'] ?? 0), 1),
                'change_24h' => round(floatval($pair['priceChange']['h24'] ?? 0), 1),
                'liq' => floatval($pair['liquidity']['usd'] ?? 0),
            ];
        }
    }
    usleep(50000);
}

// Enrich alerts with USD value and pump status
foreach ($newAlerts as &$alert) {
    $contract = $alert['contract'];
    if (isset($priceData[$contract])) {
        $pd = $priceData[$contract];
        $usdValue = $alert['amount'] * $pd['price'];
        $alert['price'] = $pd['price'];
        $alert['usd_value'] = $usdValue;
        $alert['usd_formatted'] = $usdValue >= 1e6 ? '$'.number_format($usdValue/1e6,2).'M' : ($usdValue >= 1e3 ? '$'.number_format($usdValue/1e3,1).'K' : '$'.number_format($usdValue,0));
        $alert['mc'] = $pd['mc'];
        $alert['mc_formatted'] = $pd['mc'] >= 1e6 ? '$'.number_format($pd['mc']/1e6,1).'M' : '$'.number_format($pd['mc']/1e3,0).'K';
        $alert['change_1h'] = $pd['change_1h'];
        $alert['change_24h'] = $pd['change_24h'];
        $alert['liq_formatted'] = '$'.number_format($pd['liq']/1e3,0).'K';
        $alert['is_pumping'] = $pd['change_1h'] >= 10;
        $alert['is_big_trade'] = $usdValue >= 10000;
    } else {
        $alert['usd_value'] = 0;
        $alert['usd_formatted'] = '—';
        $alert['mc_formatted'] = '—';
        $alert['change_1h'] = 0;
        $alert['change_24h'] = 0;
        $alert['liq_formatted'] = '—';
        $alert['is_pumping'] = false;
        $alert['is_big_trade'] = false;
    }
}
unset($alert);

// Merge with existing feed (newest first, max 100)
$allAlerts = array_merge($newAlerts, $existingFeed);

// Deduplicate by id
$seenIds = [];
$deduped = [];
foreach ($allAlerts as $a) {
    if (isset($seenIds[$a['id']])) continue;
    $seenIds[$a['id']] = true;
    $deduped[] = $a;
}

// Sort by timestamp (newest first)
usort($deduped, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
$deduped = array_slice($deduped, 0, 100);

// Recalculate time_ago for cached items
foreach ($deduped as &$a) {
    $a['time_ago'] = time() - $a['timestamp'];
    $a['time_display'] = $a['time_ago'] < 60 ? $a['time_ago'].'s' : ($a['time_ago'] < 3600 ? floor($a['time_ago']/60).'m' : ($a['time_ago'] < 86400 ? floor($a['time_ago']/3600).'h' : floor($a['time_ago']/86400).'d'));
}
unset($a);

// Stats
$buys = array_filter($deduped, fn($a) => $a['action'] === 'buy');
$sells = array_filter($deduped, fn($a) => $a['action'] === 'sell');
$bigTrades = array_filter($deduped, fn($a) => $a['is_big_trade'] ?? false);
$pumping = array_filter($deduped, fn($a) => $a['is_pumping'] ?? false);

$nextBatch = ($batchIndex + 1) % ceil(count($walletAddrs) / $batchSize);

$result = [
    'success' => true,
    'updated_at' => date('Y-m-d H:i:s'),
    'batch_processed' => $batchIndex,
    'next_batch' => $nextBatch,
    'wallets_in_batch' => $processed,
    'api_calls' => $apiCalls,
    'stats' => [
        'total_whales' => count($whales),
        'total_alerts' => count($deduped),
        'new_this_call' => count($newAlerts),
        'buys' => count($buys),
        'sells' => count($sells),
        'big_trades' => count($bigTrades),
        'pumping' => count($pumping),
    ],
    'alerts' => $deduped,
];

file_put_contents($cacheFile, json_encode($result));
echo json_encode($result);
