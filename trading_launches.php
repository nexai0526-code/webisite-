<?php
// trading_launches.php — Fresh Solana launches with top buyer wallets
// Uses DexScreener (free) + Helius for top holders

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$HELIUS_KEY = 'fe14718f-05d3-49d2-9880-7d0022cdbf84';
$CACHE_FILE = '/tmp/trading_launches_cache.json';
$CACHE_TTL = 120; // 2 min

// Serve cache if fresh
if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    echo file_get_contents($CACHE_FILE);
    exit;
}

function http_json($url, $method = 'GET', $body = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
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

// 1) Get fresh Solana token profiles (most recently listed)
$profiles = http_json('https://api.dexscreener.com/token-profiles/latest/v1');

// Filter to Solana only
$solana_tokens = [];
if (is_array($profiles)) {
    foreach ($profiles as $p) {
        if (($p['chainId'] ?? '') === 'solana' && !empty($p['tokenAddress'])) {
            $solana_tokens[] = $p['tokenAddress'];
            if (count($solana_tokens) >= 25) break;
        }
    }
}

// Also add top boosts (paid promotions tend to be fresh)
$boosts = http_json('https://api.dexscreener.com/token-boosts/latest/v1');
if (is_array($boosts)) {
    foreach ($boosts as $b) {
        if (($b['chainId'] ?? '') === 'solana' && !empty($b['tokenAddress'])) {
            $solana_tokens[] = $b['tokenAddress'];
            if (count($solana_tokens) >= 40) break;
        }
    }
}
$solana_tokens = array_unique($solana_tokens);

// 2) Get full pair info for each token (batch in groups of 30)
$coins = [];
$batches = array_chunk($solana_tokens, 30);

foreach ($batches as $batch) {
    $url = 'https://api.dexscreener.com/latest/dex/tokens/' . implode(',', $batch);
    $data = http_json($url);
    if (!isset($data['pairs']) || !is_array($data['pairs'])) continue;

    foreach ($data['pairs'] as $pair) {
        if (($pair['chainId'] ?? '') !== 'solana') continue;
        $created = $pair['pairCreatedAt'] ?? 0;
        if (!$created) continue;
        $age_ms = (time() * 1000) - $created;
        $age_hours = round($age_ms / 3600000, 1);
        if ($age_hours > 72) continue; // only last 3 days
        $vol = floatval($pair['volume']['h24'] ?? 0);
        if ($vol < 5000) continue; // skip dead

        $mint = $pair['baseToken']['address'] ?? '';
        $coins[$mint] = [
            'mint' => $mint,
            'pair_addr' => $pair['pairAddress'] ?? '',
            'name' => $pair['baseToken']['name'] ?? '',
            'symbol' => $pair['baseToken']['symbol'] ?? '',
            'price_usd' => floatval($pair['priceUsd'] ?? 0),
            'price_change_24h' => floatval($pair['priceChange']['h24'] ?? 0),
            'market_cap' => floatval($pair['marketCap'] ?? $pair['fdv'] ?? 0),
            'volume_24h' => $vol,
            'liquidity' => floatval($pair['liquidity']['usd'] ?? 0),
            'age_hours' => $age_hours,
            'top_buyers' => []
        ];
    }
}

// Sort by volume desc, take top 20
$coins = array_values($coins);
usort($coins, fn($a, $b) => $b['volume_24h'] <=> $a['volume_24h']);
$coins = array_slice($coins, 0, 20);

// 3) For top 10 coins, fetch top holders via Helius RPC
$top_for_holders = array_slice($coins, 0, 10);
foreach ($top_for_holders as $i => $c) {
    if (empty($c['mint'])) continue;

    $rpc = http_json(
        "https://mainnet.helius-rpc.com/?api-key=$HELIUS_KEY",
        'POST',
        [
            'jsonrpc' => '2.0',
            'id' => 'top-holders',
            'method' => 'getTokenLargestAccounts',
            'params' => [$c['mint']]
        ],
        ['Content-Type: application/json']
    );

    $top_token_accounts = $rpc['result']['value'] ?? [];
    $owner_wallets = [];

    // Top 8 token accounts → resolve to owner wallets
    foreach (array_slice($top_token_accounts, 0, 8) as $acc) {
        $token_acc = $acc['address'] ?? '';
        if (!$token_acc) continue;

        $owner_resp = http_json(
            "https://mainnet.helius-rpc.com/?api-key=$HELIUS_KEY",
            'POST',
            [
                'jsonrpc' => '2.0',
                'id' => 'owner',
                'method' => 'getAccountInfo',
                'params' => [$token_acc, ['encoding' => 'jsonParsed']]
            ],
            ['Content-Type: application/json']
        );

        $owner = $owner_resp['result']['value']['data']['parsed']['info']['owner'] ?? null;
        if ($owner && !in_array($owner, $owner_wallets)) {
            $owner_wallets[] = $owner;
        }
        if (count($owner_wallets) >= 5) break;
    }

    $coins[$i]['top_buyers'] = $owner_wallets;
}

// Determine top gainer
$top_gainer = '';
$max_chg = 0;
foreach ($coins as $c) {
    if (($c['price_change_24h'] ?? 0) > $max_chg) {
        $max_chg = $c['price_change_24h'];
        $top_gainer = $c['symbol'] . ' +' . round($c['price_change_24h']) . '%';
    }
}

$output = [
    'coins' => $coins,
    'top_gainer' => $top_gainer,
    'cached_at' => date('c'),
    'count' => count($coins)
];

$json = json_encode($output);
file_put_contents($CACHE_FILE, $json);
echo $json;
