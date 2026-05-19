<?php
// trading_whale_feed.php — Live whale buy/sell stream
// Pulls recent parsed transactions from tracked whale wallets via Helius

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$HELIUS_KEY = 'fe14718f-05d3-49d2-9880-7d0022cdbf84';
$CACHE_FILE = '/tmp/whale_feed_cache.json';
$CACHE_TTL = 60; // 1 min

if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    echo file_get_contents($CACHE_FILE);
    exit;
}

function http_json($url, $method = 'GET', $body = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
        }
    }
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// Tracked whale wallets (load discovered ones if file exists)
$TRACKED_WHALES = [
    'GjwTZ4DvJUFPyVS2adRLKgxr9CtYZ59gxAvUcvtq8wte', // Smart Money 1
    'CRVidsHrAGD3agA1WgvqVKkW6XJZbBfTRRPGq3R8R8mF', // Active Trader
    '5tzFkiKscXHK5ZXCGbXbxbckTZHWxXTXMPwQzqWFr1m9', // Binance Hot
    '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM', // Coinbase Hot
];

// Load auto-discovered whales if available
$discovered_file = '/tmp/discovered_whales.json';
if (file_exists($discovered_file)) {
    $disc = json_decode(file_get_contents($discovered_file), true);
    if (is_array($disc)) {
        foreach ($disc as $w) {
            if (!empty($w['address']) && !in_array($w['address'], $TRACKED_WHALES)) {
                $TRACKED_WHALES[] = $w['address'];
            }
        }
    }
}
$TRACKED_WHALES = array_slice($TRACKED_WHALES, 0, 8); // limit to save credits

// SOL price (cached)
$sol_price = 150;
$pf = '/tmp/sol_price.json';
if (file_exists($pf) && (time() - filemtime($pf)) < 300) {
    $sol_price = floatval(file_get_contents($pf));
} else {
    $p = http_json('https://api.coingecko.com/api/v3/simple/price?ids=solana&vs_currencies=usd');
    $sol_price = floatval($p['solana']['usd'] ?? 150);
    file_put_contents($pf, $sol_price);
}

$events = [];
$mint_set = [];
$cutoff = time() - 7200; // last 2h

foreach ($TRACKED_WHALES as $whale) {
    $txs = http_json("https://api.helius.xyz/v0/addresses/$whale/transactions?api-key=$HELIUS_KEY&limit=15");
    if (!is_array($txs)) continue;

    foreach ($txs as $tx) {
        $ts = intval($tx['timestamp'] ?? 0);
        if ($ts < $cutoff) continue;

        $swap = $tx['events']['swap'] ?? null;
        if (!$swap) continue;

        $native_in = $swap['nativeInput']['amount'] ?? 0;
        $native_out = $swap['nativeOutput']['amount'] ?? 0;
        $token_in = $swap['tokenInputs'][0] ?? null;
        $token_out = $swap['tokenOutputs'][0] ?? null;

        // BUY: native SOL in, token out
        if ($native_in > 0 && $token_out) {
            $usd = ($native_in / 1e9) * $sol_price;
            if ($usd < 2000) continue; // min $2k
            $mint = $token_out['mint'] ?? '';
            $events[] = [
                'timestamp' => $ts,
                'type' => 'buy',
                'wallet' => $whale,
                'mint' => $mint,
                'symbol' => '?',
                'usd_value' => $usd,
                'action' => 'bought'
            ];
            if ($mint) $mint_set[$mint] = true;
        }
        // SELL: token in, native SOL out
        elseif ($native_out > 0 && $token_in) {
            $usd = ($native_out / 1e9) * $sol_price;
            if ($usd < 2000) continue;
            $mint = $token_in['mint'] ?? '';
            $events[] = [
                'timestamp' => $ts,
                'type' => 'sell',
                'wallet' => $whale,
                'mint' => $mint,
                'symbol' => '?',
                'usd_value' => $usd,
                'action' => 'sold'
            ];
            if ($mint) $mint_set[$mint] = true;
        }
    }
}

// Resolve symbols via DexScreener (free, fast)
$mints = array_keys($mint_set);
$sym_map = [];
foreach (array_chunk($mints, 30) as $batch) {
    if (empty($batch)) continue;
    $data = http_json('https://api.dexscreener.com/latest/dex/tokens/' . implode(',', $batch));
    if (!isset($data['pairs'])) continue;
    foreach ($data['pairs'] as $p) {
        $m = $p['baseToken']['address'] ?? '';
        $s = $p['baseToken']['symbol'] ?? '';
        if ($m && $s && empty($sym_map[$m])) $sym_map[$m] = $s;
    }
}

foreach ($events as &$e) {
    if (!empty($sym_map[$e['mint']])) $e['symbol'] = $sym_map[$e['mint']];
}
unset($e);

// Sort newest first
usort($events, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
$events = array_slice($events, 0, 50);

$unique_wallets = count(array_unique(array_column($events, 'wallet')));
$total_volume = array_sum(array_column($events, 'usd_value'));

$output = [
    'events' => $events,
    'unique_wallets' => $unique_wallets,
    'total_volume' => $total_volume,
    'tracked' => count($TRACKED_WHALES),
    'cached_at' => date('c')
];

$json = json_encode($output);
file_put_contents($CACHE_FILE, $json);
echo $json;
