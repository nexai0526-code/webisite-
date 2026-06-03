<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$BIRDEYE_KEY = 'c9a1e2451c5b405dbfeb690eeae36b8a';
$wallet = $_GET['wallet'] ?? '';

if (!$wallet || strlen($wallet) < 32) {
    echo json_encode(['success' => false, 'error' => 'Invalid wallet address']);
    exit;
}

$cacheFile = __DIR__ . '/data/portfolio_' . substr($wallet, 0, 12) . '.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);

// Cache 10 min (rate limit protection)
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 600) {
    echo file_get_contents($cacheFile);
    exit;
}

function birdeyeCall($url, $key) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . $key,
            'accept: application/json',
            'x-chain: solana',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return null;
    return json_decode($res, true);
}

// 1. Get wallet token holdings
$holdingsUrl = "https://public-api.birdeye.so/v1/wallet/token_list?wallet={$wallet}";
$holdings = birdeyeCall($holdingsUrl, $BIRDEYE_KEY);

if (!$holdings || empty($holdings['data']['items'])) {
    $out = json_encode([
        'success' => false,
        'error' => 'No holdings found or rate limited',
        'wallet' => $wallet,
    ]);
    echo $out;
    exit;
}

$items = $holdings['data']['items'];
$totalValue = $holdings['data']['totalUsd'] ?? 0;

// Sort by value, take top 15 holdings
usort($items, fn($a, $b) => ($b['valueUsd'] ?? 0) <=> ($a['valueUsd'] ?? 0));
$items = array_slice($items, 0, 15);

$tokens = [];
foreach ($items as $it) {
    $sym = $it['symbol'] ?? substr($it['address'] ?? '?', 0, 4);
    $val = $it['valueUsd'] ?? 0;
    if ($val < 1) continue; // skip dust
    $tokens[] = [
        'symbol' => $sym,
        'name' => $it['name'] ?? $sym,
        'mint' => $it['address'] ?? '',
        'balance' => $it['uiAmount'] ?? 0,
        'value_usd' => round($val, 2),
        'price' => $it['priceUsd'] ?? 0,
        'logo' => $it['logoURI'] ?? '',
        'dex_url' => 'https://dexscreener.com/solana/' . ($it['address'] ?? ''),
    ];
}

$result = [
    'success' => true,
    'wallet' => $wallet,
    'wallet_short' => substr($wallet, 0, 6) . '...' . substr($wallet, -4),
    'updated_at' => date('Y-m-d H:i:s'),
    'total_value' => round($totalValue, 2),
    'token_count' => count($tokens),
    'holdings' => $tokens,
    'solscan' => "https://solscan.io/account/{$wallet}",
];

file_put_contents($cacheFile, json_encode($result));
echo json_encode($result);
