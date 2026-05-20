<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ETHERSCAN_KEY = 'TXMMKSSBMMYF1FF7DGNRHE4ASW2WIKWS43';
$walletsFile = __DIR__ . '/data/eth_smart_wallets.json';
$profilesFile = __DIR__ . '/data/eth_whale_profiles.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);

if (file_exists($profilesFile) && (time() - filemtime($profilesFile)) < 600) {
    echo file_get_contents($profilesFile);
    exit;
}

function fetchJson($url, $timeout = 8) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NexAI/1.0');
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// Step 1: Load or discover smart wallets
$smartWallets = file_exists($walletsFile) ? json_decode(file_get_contents($walletsFile), true) : [];
if (!is_array($smartWallets)) $smartWallets = [];

// Discover NEW wallets from new_coin_whales cache
$newCoinsCache = __DIR__ . '/data/new_coin_whales.json';
if (file_exists($newCoinsCache)) {
    $cacheData = json_decode(file_get_contents($newCoinsCache), true);
    if (isset($cacheData['coins'])) {
        foreach ($cacheData['coins'] as $coin) {
            if (!isset($coin['whales']) || empty($coin['whales'])) continue;
            // Only add HOLDING whales with net > $5K
            foreach ($coin['whales'] as $w) {
                if ($w['type'] !== 'holding') continue;
                if (abs($w['net_usd']) < 5000) continue;
                $addr = strtolower($w['wallet_full']);
                if (!isset($smartWallets[$addr])) {
                    $smartWallets[$addr] = [
                        'address' => $addr,
                        'discovered_at' => time(),
                        'discovered_from' => $coin['symbol'],
                        'score' => 50,
                    ];
                } else {
                    $smartWallets[$addr]['score'] = min(100, $smartWallets[$addr]['score'] + 5);
                }
            }
        }
    }
}

// Keep top 30 wallets by score
uasort($smartWallets, fn($a,$b) => $b['score'] - $a['score']);
$smartWallets = array_slice($smartWallets, 0, 30, true);
file_put_contents($walletsFile, json_encode($smartWallets));

// Step 2: For each wallet, get their last 7 days of token transactions
$weekAgo = time() - (7 * 86400);
$profiles = [];

foreach ($smartWallets as $addr => $info) {
    $url = "https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokentx&address={$addr}&page=1&offset=100&sort=desc&apikey={$ETHERSCAN_KEY}";
    $data = fetchJson($url, 8);
    if (!isset($data['result']) || !is_array($data['result'])) continue;
    
    // Group by token
    $tokens = [];
    foreach ($data['result'] as $tx) {
        $timestamp = intval($tx['timeStamp'] ?? 0);
        if ($timestamp < $weekAgo) continue;
        
        $from = strtolower($tx['from'] ?? '');
        $to = strtolower($tx['to'] ?? '');
        $contract = strtolower($tx['contractAddress'] ?? '');
        $symbol = $tx['tokenSymbol'] ?? 'UNKNOWN';
        $decimals = intval($tx['tokenDecimal'] ?? 18);
        $amount = floatval($tx['value'] ?? 0) / pow(10, $decimals);
        if ($amount < 1) continue;
        
        // Skip stablecoins/wrapped
        if (in_array($symbol, ['USDC', 'USDT', 'DAI', 'WETH', 'BUSD'])) continue;
        
        $action = $to === $addr ? 'buy' : ($from === $addr ? 'sell' : null);
        if (!$action) continue;
        
        if (!isset($tokens[$contract])) {
            $tokens[$contract] = [
                'symbol' => $symbol,
                'contract' => $contract,
                'bought' => 0,
                'sold' => 0,
                'count_buys' => 0,
                'count_sells' => 0,
                'first_buy_time' => 0,
                'last_action_time' => 0,
                'last_action' => '',
            ];
        }
        
        if ($action === 'buy') {
            $tokens[$contract]['bought'] += $amount;
            $tokens[$contract]['count_buys']++;
            if ($tokens[$contract]['first_buy_time'] === 0 || $timestamp < $tokens[$contract]['first_buy_time']) {
                $tokens[$contract]['first_buy_time'] = $timestamp;
            }
        } else {
            $tokens[$contract]['sold'] += $amount;
            $tokens[$contract]['count_sells']++;
        }
        
        if ($timestamp > $tokens[$contract]['last_action_time']) {
            $tokens[$contract]['last_action_time'] = $timestamp;
            $tokens[$contract]['last_action'] = $action;
        }
    }
    
    // Get current price for each token from DexScreener
    $coinStatus = [];
    foreach ($tokens as $contract => $t) {
        if ($t['bought'] === 0.0) continue;
        
        $netHeld = $t['bought'] - $t['sold'];
        $isHolding = $netHeld > ($t['bought'] * 0.1); // Still holds > 10%
        $isSold = $netHeld < ($t['bought'] * 0.1);
        
        // Get current price
        $price = 0;
        $change24h = 0;
        $dexUrl = '';
        $pd = fetchJson("https://api.dexscreener.com/latest/dex/tokens/{$contract}", 4);
        if (isset($pd['pairs'][0])) {
            $ethPairs = array_filter($pd['pairs'], fn($p) => ($p['chainId']??'') === 'ethereum');
            if (!empty($ethPairs)) {
                $pair = array_values($ethPairs)[0];
                $price = floatval($pair['priceUsd'] ?? 0);
                $change24h = floatval($pair['priceChange']['h24'] ?? 0);
                $dexUrl = $pair['url'] ?? '';
            }
        }
        
        $boughtUsd = $t['bought'] * $price;
        $soldUsd = $t['sold'] * $price;
        $heldUsd = $netHeld * $price;
        
        // Skip if too small
        if ($boughtUsd < 500 && $heldUsd < 500) continue;
        
        $status = 'unknown';
        $statusColor = '#9B9BB0';
        if ($isHolding) {
            $status = 'HOLDING';
            $statusColor = $change24h >= 0 ? '#00E676' : '#FFD32A';
        } elseif ($isSold) {
            // Determine if profit or loss based on 24h change as proxy
            if ($change24h >= 50) {
                $status = 'SOLD (TOP)';
                $statusColor = '#FF4757'; // sold too early
            } elseif ($change24h <= -30) {
                $status = 'DUMPED';
                $statusColor = '#00E676'; // wise exit
            } else {
                $status = 'EXITED';
                $statusColor = '#FFD32A';
            }
        }
        
        $age = time() - $t['first_buy_time'];
        $ageDisplay = $age < 3600 ? floor($age/60).'m' : ($age < 86400 ? floor($age/3600).'h' : floor($age/86400).'d');
        
        $coinStatus[] = [
            'symbol' => $t['symbol'],
            'contract' => $contract,
            'status' => $status,
            'status_color' => $statusColor,
            'bought_amount' => $t['bought'],
            'sold_amount' => $t['sold'],
            'held_amount' => $netHeld,
            'bought_usd' => $boughtUsd,
            'bought_usd_formatted' => $boughtUsd >= 1e6 ? '$'.number_format($boughtUsd/1e6,2).'M' : '$'.number_format($boughtUsd/1e3,1).'K',
            'held_usd' => $heldUsd,
            'held_usd_formatted' => abs($heldUsd) >= 1e6 ? '$'.number_format(abs($heldUsd)/1e6,2).'M' : '$'.number_format(abs($heldUsd)/1e3,1).'K',
            'count_buys' => $t['count_buys'],
            'count_sells' => $t['count_sells'],
            'last_action' => $t['last_action'],
            'first_buy_age' => $ageDisplay,
            'change_24h' => round($change24h, 1),
            'dex_url' => $dexUrl ?: "https://dexscreener.com/ethereum/{$contract}",
        ];
    }
    
    if (empty($coinStatus)) continue;
    
    // Sort by held USD value
    usort($coinStatus, fn($a, $b) => abs($b['held_usd']) <=> abs($a['held_usd']));
    
    // Calculate stats
    $totalBought = array_sum(array_column($coinStatus, 'bought_usd'));
    $totalHeld = array_sum(array_map(fn($c) => max(0, $c['held_usd']), $coinStatus));
    $holdingCount = count(array_filter($coinStatus, fn($c) => $c['status'] === 'HOLDING'));
    $exitedCount = count(array_filter($coinStatus, fn($c) => in_array($c['status'], ['EXITED','SOLD (TOP)','DUMPED'])));
    
    // Win rate (HOLDING + DUMPED in profit + EXITED in profit)
    $wins = 0; $losses = 0;
    foreach ($coinStatus as $c) {
        if ($c['status'] === 'HOLDING' && $c['change_24h'] >= 0) $wins++;
        elseif ($c['status'] === 'DUMPED') $wins++;
        elseif ($c['status'] === 'SOLD (TOP)') $losses++;
        elseif ($c['status'] === 'HOLDING' && $c['change_24h'] < -20) $losses++;
    }
    $totalTrades = $wins + $losses;
    $winRate = $totalTrades > 0 ? round(($wins / $totalTrades) * 100) : 0;
    
    $profiles[] = [
        'wallet' => substr($addr, 0, 6) . '...' . substr($addr, -4),
        'wallet_full' => $addr,
        'score' => $info['score'],
        'discovered_from' => $info['discovered_from'] ?? 'manual',
        'coins_count' => count($coinStatus),
        'holding_count' => $holdingCount,
        'exited_count' => $exitedCount,
        'total_bought_usd' => $totalBought,
        'total_bought_formatted' => $totalBought >= 1e6 ? '$'.number_format($totalBought/1e6,2).'M' : '$'.number_format($totalBought/1e3,0).'K',
        'total_held_usd' => $totalHeld,
        'total_held_formatted' => $totalHeld >= 1e6 ? '$'.number_format($totalHeld/1e6,2).'M' : '$'.number_format($totalHeld/1e3,0).'K',
        'win_rate' => $winRate,
        'wins' => $wins,
        'losses' => $losses,
        'coins' => array_slice($coinStatus, 0, 15),
        'explorer_url' => "https://etherscan.io/address/{$addr}",
    ];
    
    usleep(300000);
}

// Sort by total held USD (biggest portfolio first)
usort($profiles, fn($a, $b) => $b['total_held_usd'] <=> $a['total_held_usd']);

$result = [
    'success' => true,
    'updated_at' => date('Y-m-d H:i:s'),
    'stats' => [
        'total_wallets' => count($profiles),
        'total_portfolio' => array_sum(array_column($profiles, 'total_held_usd')),
        'total_coins' => array_sum(array_column($profiles, 'coins_count')),
        'avg_win_rate' => count($profiles) > 0 ? round(array_sum(array_column($profiles, 'win_rate')) / count($profiles)) : 0,
    ],
    'profiles' => $profiles,
];

file_put_contents($profilesFile, json_encode($result));
echo json_encode($result);
