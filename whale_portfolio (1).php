<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$HELIUS_KEY = 'fe14718f-05d3-49d2-9880-7d0022cdbf84';
$wallet = $_GET['wallet'] ?? '';

if (!$wallet || strlen($wallet) < 32) {
    echo json_encode(['success' => false, 'error' => 'Invalid wallet address']);
    exit;
}

$cacheFile = __DIR__ . '/data/portfolio_' . substr($wallet, 0, 12) . '.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);

// Cache 5 min
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
    echo file_get_contents($cacheFile);
    exit;
}

// Helius DAS getAssetsByOwner - holdings + prices in one call
$payload = json_encode([
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'getAssetsByOwner',
    'params' => [
        'ownerAddress' => $wallet,
        'page' => 1,
        'limit' => 200,
        'displayOptions' => ['showFungible' => true, 'showNativeBalance' => true],
    ],
]);

$ch = curl_init("https://mainnet.helius-rpc.com/?api-key={$HELIUS_KEY}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 20,
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !$res) {
    echo json_encode(['success' => false, 'error' => 'Helius API error', 'wallet' => $wallet]);
    exit;
}

$data = json_decode($res, true);
$r = $data['result'] ?? [];
$items = $r['items'] ?? [];

$tokens = [];
$totalValue = 0;

// Native SOL
$nb = $r['nativeBalance'] ?? [];
$solValue = $nb['total_price'] ?? 0;
$solAmount = ($nb['lamports'] ?? 0) / 1e9;
if ($solValue > 0.5) {
    $tokens[] = [
        'symbol' => 'SOL',
        'name' => 'Solana',
        'mint' => 'So11111111111111111111111111111111111111112',
        'balance' => round($solAmount, 4),
        'value_usd' => round($solValue, 2),
        'price' => round(($nb['price_per_sol'] ?? 0), 2),
        'dex_url' => 'https://dexscreener.com/solana/So11111111111111111111111111111111111111112',
    ];
    $totalValue += $solValue;
}

// Fungible tokens with value
foreach ($items as $it) {
    $iface = $it['interface'] ?? '';
    if (!in_array($iface, ['FungibleToken', 'FungibleAsset'])) continue;
    $ti = $it['token_info'] ?? [];
    $pi = $ti['price_info'] ?? [];
    $val = $pi['total_price'] ?? 0;
    if ($val < 1) continue; // skip dust
    $bal = ($ti['balance'] ?? 0) / pow(10, $ti['decimals'] ?? 0);
    $tokens[] = [
        'symbol' => $ti['symbol'] ?? substr($it['id'] ?? '?', 0, 4),
        'name' => $it['content']['metadata']['name'] ?? ($ti['symbol'] ?? '?'),
        'mint' => $it['id'] ?? '',
        'balance' => round($bal, 4),
        'value_usd' => round($val, 2),
        'price' => $pi['price_per_token'] ?? 0,
        'dex_url' => 'https://dexscreener.com/solana/' . ($it['id'] ?? ''),
    ];
    $totalValue += $val;
}

// Sort by value
usort($tokens, fn($a, $b) => $b['value_usd'] <=> $a['value_usd']);
$tokens = array_slice($tokens, 0, 20);

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
