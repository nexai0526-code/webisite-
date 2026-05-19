<?php
// trading_wallet.php — Full A2Z Solana wallet profile
// Returns: SOL balance, USD value, holdings, buys/sells 24h, funding source

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$HELIUS_KEY = 'fe14718f-05d3-49d2-9880-7d0022cdbf84';
$addr = trim($_GET['addr'] ?? '');

if (!$addr || strlen($addr) < 30 || strlen($addr) > 50) {
    echo json_encode(['error' => 'Invalid wallet address']);
    exit;
}

$CACHE_FILE = '/tmp/wallet_' . substr(md5($addr), 0, 16) . '.json';
$CACHE_TTL = 180; // 3 min
if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    echo file_get_contents($CACHE_FILE);
    exit;
}

function http_json($url, $method = 'GET', $body = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 18,
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

// Known exchange / source labels (truncated -> label)
$LABELS = [
    '5tzFkiKscXHK5ZXCGbXbxbckTZHWxXTXMPwQzqWFr1m9' => 'Binance Hot',
    '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM' => 'Coinbase Hot',
    'AC5RDfQFmDS1deWZos921JfqscXdByf8BKHs5ACWjtW2' => 'Bybit Hot',
    'GjwTZ4DvJUFPyVS2adRLKgxr9CtYZ59gxAvUcvtq8wte' => 'OKX Hot',
];

// 1) SOL balance
$bal_resp = http_json(
    "https://mainnet.helius-rpc.com/?api-key=$HELIUS_KEY",
    'POST',
    ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'getBalance', 'params' => [$addr]],
    ['Content-Type: application/json']
);
$lamports = $bal_resp['result']['value'] ?? 0;
$sol_balance = $lamports / 1e9;

// SOL price from CoinGecko (cached separately)
$sol_price = 0;
$pf = '/tmp/sol_price.json';
if (file_exists($pf) && (time() - filemtime($pf)) < 300) {
    $sol_price = floatval(file_get_contents($pf));
} else {
    $p = http_json('https://api.coingecko.com/api/v3/simple/price?ids=solana&vs_currencies=usd');
    $sol_price = floatval($p['solana']['usd'] ?? 150);
    file_put_contents($pf, $sol_price);
}

// 2) Token holdings via DAS getAssetsByOwner
$das = http_json(
    "https://mainnet.helius-rpc.com/?api-key=$HELIUS_KEY",
    'POST',
    [
        'jsonrpc' => '2.0',
        'id' => 'assets',
        'method' => 'getAssetsByOwner',
        'params' => [
            'ownerAddress' => $addr,
            'page' => 1,
            'limit' => 100,
            'displayOptions' => ['showFungible' => true, 'showNativeBalance' => false]
        ]
    ],
    ['Content-Type: application/json']
);

$holdings = [];
$items = $das['result']['items'] ?? [];
foreach ($items as $it) {
    $interface = $it['interface'] ?? '';
    if ($interface !== 'FungibleToken' && $interface !== 'FungibleAsset') continue;

    $info = $it['token_info'] ?? [];
    $amount = floatval($info['balance'] ?? 0);
    $decimals = intval($info['decimals'] ?? 0);
    $actual = $decimals > 0 ? $amount / pow(10, $decimals) : $amount;
    if ($actual <= 0) continue;

    $price = floatval($info['price_info']['price_per_token'] ?? 0);
    $usd = $actual * $price;

    $sym = $info['symbol'] ?? ($it['content']['metadata']['symbol'] ?? '?');

    $holdings[] = [
        'mint' => $it['id'] ?? '',
        'symbol' => $sym,
        'amount' => $actual,
        'price' => $price,
        'usd_value' => $usd
    ];
}

// Sort holdings by USD value
usort($holdings, fn($a, $b) => $b['usd_value'] <=> $a['usd_value']);
$top_holdings = array_slice($holdings, 0, 10);
$total_token_usd = array_sum(array_column($holdings, 'usd_value'));

// 3) Parsed transactions (last 100)
$tx_data = http_json(
    "https://api.helius.xyz/v0/addresses/$addr/transactions?api-key=$HELIUS_KEY&limit=100"
);

$recent_buys = [];
$recent_sells = [];
$funding_source = null;
$first_seen_ts = null;
$cutoff_24h = time() - 86400;

if (is_array($tx_data)) {
    foreach ($tx_data as $tx) {
        $ts = intval($tx['timestamp'] ?? 0);
        if ($ts && (!$first_seen_ts || $ts < $first_seen_ts)) $first_seen_ts = $ts;

        $type = $tx['type'] ?? '';
        $events = $tx['events'] ?? [];

        // Look for SWAP events (buys/sells)
        if (!empty($events['swap'])) {
            $swap = $events['swap'];
            $native_in = $swap['nativeInput']['amount'] ?? 0;
            $native_out = $swap['nativeOutput']['amount'] ?? 0;
            $token_in = $swap['tokenInputs'][0] ?? null;
            $token_out = $swap['tokenOutputs'][0] ?? null;

            $is_buy = $native_in > 0 || (!empty($swap['tokenInputs']) && in_array($swap['tokenInputs'][0]['mint'] ?? '', [
                'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v', // USDC
                'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB'  // USDT
            ]));

            if ($token_out && $ts >= $cutoff_24h) {
                $usd_est = $native_in > 0 ? ($native_in / 1e9) * $sol_price : 0;
                if (!$usd_est && $token_in) {
                    // USDC/USDT input
                    $dec = $token_in['rawTokenAmount']['decimals'] ?? 6;
                    $amt = floatval($token_in['rawTokenAmount']['tokenAmount'] ?? 0);
                    if ($dec > 0) $amt = $amt / pow(10, $dec);
                    $usd_est = $amt;
                }

                $entry = [
                    'timestamp' => $ts,
                    'mint' => $token_out['mint'] ?? '',
                    'symbol' => 'TOKEN',
                    'usd_value' => $usd_est,
                ];
                if ($is_buy || $native_in > 0) $recent_buys[] = $entry;
                else $recent_sells[] = $entry;
            }

            // Sell: native output (got SOL back)
            if ($native_out > 0 && $token_in && $ts >= $cutoff_24h) {
                $usd_est = ($native_out / 1e9) * $sol_price;
                $recent_sells[] = [
                    'timestamp' => $ts,
                    'mint' => $token_in['mint'] ?? '',
                    'symbol' => 'TOKEN',
                    'usd_value' => $usd_est,
                ];
            }
        }

        // Detect funding source from oldest transfer IN
        if ($type === 'TRANSFER' && empty($funding_source)) {
            $native = $tx['nativeTransfers'] ?? [];
            foreach ($native as $t) {
                if (($t['toUserAccount'] ?? '') === $addr && ($t['amount'] ?? 0) > 1e8) {
                    $funding_source = $t['fromUserAccount'] ?? null;
                    break;
                }
            }
        }
    }
}

// Enrich token symbols via metadata batch
$all_mints = array_unique(array_filter(array_merge(
    array_column($recent_buys, 'mint'),
    array_column($recent_sells, 'mint')
)));

if (!empty($all_mints)) {
    $meta = http_json(
        "https://api.helius.xyz/v0/token-metadata?api-key=$HELIUS_KEY",
        'POST',
        ['mintAccounts' => array_values(array_slice($all_mints, 0, 30))],
        ['Content-Type: application/json']
    );

    $sym_map = [];
    if (is_array($meta)) {
        foreach ($meta as $m) {
            $mint = $m['account'] ?? '';
            $sym = $m['onChainMetadata']['metadata']['data']['symbol']
                ?? $m['offChainMetadata']['metadata']['symbol']
                ?? '';
            if ($mint && $sym) $sym_map[$mint] = trim($sym);
        }
    }

    foreach ($recent_buys as &$r) {
        if (!empty($sym_map[$r['mint']])) $r['symbol'] = $sym_map[$r['mint']];
    }
    foreach ($recent_sells as &$r) {
        if (!empty($sym_map[$r['mint']])) $r['symbol'] = $sym_map[$r['mint']];
    }
    unset($r);
}

$funding_label = $funding_source && isset($LABELS[$funding_source]) ? $LABELS[$funding_source] : 'External wallet';

$output = [
    'address' => $addr,
    'sol_balance' => round($sol_balance, 4),
    'usd_value' => round(($sol_balance * $sol_price) + $total_token_usd, 2),
    'token_count' => count($holdings),
    'top_holdings' => $top_holdings,
    'recent_buys' => array_slice($recent_buys, 0, 15),
    'recent_sells' => array_slice($recent_sells, 0, 15),
    'funding_source' => $funding_source,
    'funding_label' => $funding_label,
    'first_seen' => $first_seen_ts ? date('Y-m-d', $first_seen_ts) : null,
    'cached_at' => date('c')
];

$json = json_encode($output);
file_put_contents($CACHE_FILE, $json);
echo $json;
