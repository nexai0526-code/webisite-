<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$HELIUS_KEY = 'fe14718f-05d3-49d2-9880-7d0022cdbf84';
$wallet = $_GET['wallet'] ?? '';
$mint   = $_GET['mint'] ?? '';

if (!$wallet || strlen($wallet) < 32 || !$mint) {
    echo json_encode(['success' => false, 'error' => 'wallet + mint required']);
    exit;
}

$cacheFile = __DIR__ . '/data/holds_' . substr($wallet,0,8) . '_' . substr($mint,0,8) . '.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 180) {
    echo file_get_contents($cacheFile);
    exit;
}

// Helius DAS getAssetsByOwner - find if this mint is still held
$payload = json_encode([
    'jsonrpc' => '2.0', 'id' => 1, 'method' => 'getAssetsByOwner',
    'params' => [
        'ownerAddress' => $wallet, 'page' => 1, 'limit' => 100,
        'displayOptions' => ['showFungible' => true, 'showNativeBalance' => true],
    ],
]);

$ch = curl_init("https://mainnet.helius-rpc.com/?api-key={$HELIUS_KEY}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 40,
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !$res) {
    echo json_encode(['success' => false, 'error' => 'Helius error', 'status' => 'UNKNOWN']);
    exit;
}

$data = json_decode($res, true);
$items = $data['result']['items'] ?? [];

$holds = false;
$balance = 0;
$valueUsd = 0;

// Native SOL special case
if ($mint === 'So11111111111111111111111111111111111111112') {
    $nb = $data['result']['nativeBalance'] ?? [];
    $solAmt = ($nb['lamports'] ?? 0) / 1e9;
    if ($solAmt > 0.001) {
        $holds = true; $balance = $solAmt; $valueUsd = $nb['total_price'] ?? 0;
    }
}

foreach ($items as $it) {
    if (($it['id'] ?? '') !== $mint) continue;
    $ti = $it['token_info'] ?? [];
    $bal = ($ti['balance'] ?? 0) / pow(10, $ti['decimals'] ?? 0);
    if ($bal > 0) {
        $holds = true;
        $balance = $bal;
        $valueUsd = $ti['price_info']['total_price'] ?? 0;
    }
    break;
}

$status = $holds ? 'HOLDING' : 'SOLD';
$result = [
    'success'  => true,
    'wallet'   => substr($wallet,0,6).'...'.substr($wallet,-4),
    'mint'     => $mint,
    'status'   => $status,
    'holds'    => $holds,
    'balance'  => round($balance, 4),
    'value_usd'=> round($valueUsd, 2),
    'color'    => $holds ? '#14F195' : '#FF4757',
    'updated_at' => date('Y-m-d H:i:s'),
];
file_put_contents($cacheFile, json_encode($result));
echo json_encode($result);
