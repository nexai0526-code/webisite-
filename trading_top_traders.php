<?php
// trading_top_traders.php — Top trader leaderboard
// Builds from whale_feed cache + computes 24h PnL estimates

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$CACHE_FILE = '/tmp/top_traders_cache.json';
$CACHE_TTL = 300; // 5 min

if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    echo file_get_contents($CACHE_FILE);
    exit;
}

$LABELS = [
    'GjwTZ4DvJUFPyVS2adRLKgxr9CtYZ59gxAvUcvtq8wte' => 'Smart Money',
    'CRVidsHrAGD3agA1WgvqVKkW6XJZbBfTRRPGq3R8R8mF' => 'Active Trader',
    '5tzFkiKscXHK5ZXCGbXbxbckTZHWxXTXMPwQzqWFr1m9' => 'Binance',
    '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM' => 'Coinbase',
];

// Aggregate from whale feed cache
$traders = [];
$feed_file = '/tmp/whale_feed_cache.json';
if (file_exists($feed_file)) {
    $feed = json_decode(file_get_contents($feed_file), true);
    foreach (($feed['events'] ?? []) as $e) {
        $w = $e['wallet'];
        if (!isset($traders[$w])) {
            $traders[$w] = [
                'wallet' => $w,
                'label' => $LABELS[$w] ?? '',
                'buys_usd' => 0, 'sells_usd' => 0,
                'trades' => 0, 'wins' => 0,
                'best_call' => '', 'best_call_usd' => 0
            ];
        }
        $traders[$w]['trades']++;
        if ($e['type'] === 'buy') $traders[$w]['buys_usd'] += $e['usd_value'];
        else $traders[$w]['sells_usd'] += $e['usd_value'];

        if ($e['usd_value'] > $traders[$w]['best_call_usd']) {
            $traders[$w]['best_call_usd'] = $e['usd_value'];
            $traders[$w]['best_call'] = ($e['symbol'] ?? '?') . ' ' . number_format($e['usd_value']/1000, 1) . 'K';
        }
    }
}

// Also load auto-discovered whales
$disc_file = '/tmp/discovered_whales.json';
if (file_exists($disc_file)) {
    $disc = json_decode(file_get_contents($disc_file), true);
    if (is_array($disc)) {
        foreach ($disc as $w) {
            $addr = $w['address'] ?? '';
            if (!$addr || isset($traders[$addr])) continue;
            $traders[$addr] = [
                'wallet' => $addr,
                'label' => '🤖 Auto',
                'buys_usd' => floatval($w['volume_usd'] ?? 0),
                'sells_usd' => 0,
                'trades' => intval($w['trades'] ?? 1),
                'best_call' => $w['best_token'] ?? '',
                'best_call_usd' => 0
            ];
        }
    }
}

// Compute PnL = sells - buys (rough proxy)
foreach ($traders as &$t) {
    $t['pnl_24h'] = $t['sells_usd'] - $t['buys_usd'];
    // Win rate proxy: % of trades that were sells (taking profit)
    $t['win_rate'] = $t['trades'] > 0
        ? min(95, max(20, 50 + ($t['pnl_24h'] / max(1, $t['buys_usd'] + $t['sells_usd'])) * 100))
        : 0;
}
unset($t);

// Sort by absolute volume (most active first)
$traders = array_values($traders);
usort($traders, function($a, $b) {
    return ($b['buys_usd'] + $b['sells_usd']) <=> ($a['buys_usd'] + $a['sells_usd']);
});
$traders = array_slice($traders, 0, 25);

$output = [
    'traders' => $traders,
    'cached_at' => date('c'),
    'note' => 'PnL based on buy/sell volume in last 2h whale feed'
];

$json = json_encode($output);
file_put_contents($CACHE_FILE, $json);
echo $json;
