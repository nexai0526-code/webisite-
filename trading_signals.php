<?php
// trading_signals.php — Copy-trade signals
// Detects: multiple whales bought same coin in short time window

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$CACHE_FILE = '/tmp/trading_signals_cache.json';
$CACHE_TTL = 120;

if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    echo file_get_contents($CACHE_FILE);
    exit;
}

// Read whale feed
$feed_file = '/tmp/whale_feed_cache.json';
if (!file_exists($feed_file)) {
    echo json_encode(['signals' => [], 'note' => 'Whale feed not loaded yet']);
    exit;
}

$feed = json_decode(file_get_contents($feed_file), true);
$events = $feed['events'] ?? [];

// Group BUYS by mint
$clusters = [];
foreach ($events as $e) {
    if ($e['type'] !== 'buy' || empty($e['mint'])) continue;
    $m = $e['mint'];
    if (!isset($clusters[$m])) {
        $clusters[$m] = [
            'mint' => $m,
            'symbol' => $e['symbol'] ?? '?',
            'wallets' => [],
            'total_usd' => 0,
            'first_ts' => $e['timestamp'],
            'last_ts' => $e['timestamp']
        ];
    }
    if (!in_array($e['wallet'], $clusters[$m]['wallets'])) {
        $clusters[$m]['wallets'][] = $e['wallet'];
    }
    $clusters[$m]['total_usd'] += $e['usd_value'];
    $clusters[$m]['last_ts'] = max($clusters[$m]['last_ts'], $e['timestamp']);
    $clusters[$m]['first_ts'] = min($clusters[$m]['first_ts'], $e['timestamp']);
}

// Filter: 2+ unique whales bought same coin
$signals = [];
function http_json_simple($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
    $r = curl_exec($ch); curl_close($ch);
    return json_decode($r, true);
}

foreach ($clusters as $c) {
    if (count($c['wallets']) < 2) continue;

    $window_sec = $c['last_ts'] - $c['first_ts'];
    $window_str = $window_sec < 60 ? $window_sec . 's'
                : ($window_sec < 3600 ? round($window_sec / 60) . 'm'
                : round($window_sec / 3600, 1) . 'h');

    // Fetch market cap from DexScreener
    $mc = 0;
    $info = http_json_simple('https://api.dexscreener.com/latest/dex/tokens/' . $c['mint']);
    if (!empty($info['pairs'][0])) {
        $mc = floatval($info['pairs'][0]['marketCap'] ?? $info['pairs'][0]['fdv'] ?? 0);
        if (empty($c['symbol']) || $c['symbol'] === '?') {
            $c['symbol'] = $info['pairs'][0]['baseToken']['symbol'] ?? '?';
        }
    }

    $signals[] = [
        'mint' => $c['mint'],
        'symbol' => $c['symbol'],
        'whale_count' => count($c['wallets']),
        'wallets' => $c['wallets'],
        'total_usd' => $c['total_usd'],
        'market_cap' => $mc,
        'window' => $window_str
    ];
}

// Sort by whale count desc, then by total usd
usort($signals, function($a, $b) {
    if ($a['whale_count'] !== $b['whale_count']) return $b['whale_count'] <=> $a['whale_count'];
    return $b['total_usd'] <=> $a['total_usd'];
});

$signals = array_slice($signals, 0, 15);

$output = [
    'signals' => $signals,
    'cached_at' => date('c'),
    'note' => 'Coins bought by 2+ whales in last 2h'
];

$json = json_encode($output);
file_put_contents($CACHE_FILE, $json);
echo $json;
