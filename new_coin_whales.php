<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ETHERSCAN_KEY = 'TXMMKSSBMMYF1FF7DGNRHE4ASW2WIKWS43';
$cacheFile = __DIR__ . '/data/new_coin_whales.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 180) {
    echo file_get_contents($cacheFile);
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

// Step 1: Collect ETH new coins
$ethCoins = [];

$profiles = fetchJson('https://api.dexscreener.com/token-profiles/latest/v1');
if (is_array($profiles)) {
    foreach ($profiles as $p) {
        if (($p['chainId']??'') !== 'ethereum') continue;
        $tokenAddr = $p['tokenAddress'] ?? '';
        if ($tokenAddr) $ethCoins[strtolower($tokenAddr)] = true;
        if (count($ethCoins) >= 20) break;
    }
}

$searchData = fetchJson('https://api.dexscreener.com/latest/dex/search?q=WETH');
if (isset($searchData['pairs'])) {
    foreach ($searchData['pairs'] as $p) {
        if (($p['chainId']??'') !== 'ethereum') continue;
        $createdAt = intval($p['pairCreatedAt'] ?? 0) / 1000;
        $ageHours = $createdAt > 0 ? (time() - $createdAt) / 3600 : 999;
        if ($ageHours > 72) continue;
        $addr = strtolower($p['baseToken']['address'] ?? '');
        if ($addr) $ethCoins[$addr] = true;
        if (count($ethCoins) >= 30) break;
    }
}

$newCoins = [];
foreach (array_keys($ethCoins) as $tokenAddr) {
    if (count($newCoins) >= 12) break;

    $pd = fetchJson("https://api.dexscreener.com/latest/dex/tokens/{$tokenAddr}", 5);
    if (!isset($pd['pairs'][0])) continue;

    $ethPairs = array_filter($pd['pairs'], fn($p) => ($p['chainId']??'') === 'ethereum');
    if (empty($ethPairs)) continue;
    usort($ethPairs, fn($a,$b) => floatval($b['liquidity']['usd']??0) <=> floatval($a['liquidity']['usd']??0));
    $pair = array_values($ethPairs)[0];

    $createdAt = intval($pair['pairCreatedAt'] ?? 0) / 1000;
    $ageHours = $createdAt > 0 ? round((time() - $createdAt) / 3600, 1) : 999;
    if ($ageHours > 72) continue;

    $volume24h = floatval($pair['volume']['h24'] ?? 0);
    $liquidity = floatval($pair['liquidity']['usd'] ?? 0);
    $liquidityEth = $liquidity / 3500;
    if ($volume24h < 15000 || $liquidity < 5000) continue;

    $buys5m = intval($pair['txns']['m5']['buys'] ?? 0);
    $buys1h = intval($pair['txns']['h1']['buys'] ?? 0);

    $newCoins[] = [
        'symbol' => $pair['baseToken']['symbol'] ?? 'TOKEN',
        'name' => $pair['baseToken']['name'] ?? '',
        'address' => $tokenAddr,
        'age_hours' => $ageHours,
        'age_display' => $ageHours < 1 ? round($ageHours * 60) . 'm' : round($ageHours, 1) . 'h',
        'price' => floatval($pair['priceUsd'] ?? 0),
        'price_formatted' => floatval($pair['priceUsd']??0) >= 1 ? '$'.number_format(floatval($pair['priceUsd']),4) : '$'.number_format(floatval($pair['priceUsd']),9),
        'change_5m' => round(floatval($pair['priceChange']['m5'] ?? 0), 1),
        'change_1h' => round(floatval($pair['priceChange']['h1'] ?? 0), 1),
        'change_24h' => round(floatval($pair['priceChange']['h24'] ?? 0), 1),
        'volume_24h' => $volume24h,
        'volume_formatted' => $volume24h >= 1e6 ? '$'.number_format($volume24h/1e6,2).'M' : '$'.number_format($volume24h/1e3,0).'K',
        'liquidity' => $liquidity,
        'liquidity_eth' => round($liquidityEth, 2),
        'liquidity_formatted' => '$'.number_format($liquidity/1e3,0).'K',
        'market_cap' => floatval($pair['marketCap'] ?? 0),
        'mc_formatted' => floatval($pair['marketCap']??0) >= 1e6 ? '$'.number_format(floatval($pair['marketCap'])/1e6,1).'M' : '$'.number_format(floatval($pair['marketCap']??0)/1e3,0).'K',
        'buys_5m' => $buys5m,
        'buys_1h' => $buys1h,
        'sells_1h' => intval($pair['txns']['h1']['sells'] ?? 0),
        'dex_url' => $pair['url'] ?? '',
    ];
    usleep(50000);
}

// Step 2: Safety checks + whales
foreach ($newCoins as &$coin) {

    // GoPlus Security
    $goplusData = fetchJson("https://api.gopluslabs.io/api/v1/token_security/1?contract_addresses={$coin['address']}", 6);
    $sec = $goplusData['result'][strtolower($coin['address'])] ?? null;

    $checks = [];
    $safetyScore = 0;
    $totalChecks = 10;

    $checks['liquidity'] = ['pass' => $coin['liquidity_eth'] >= 2, 'label' => 'Liq > 2 ETH', 'value' => $coin['liquidity_eth'].' ETH'];

    if ($sec) {
        $lpLocked = false;
        $lpBurnedPct = 0;
        if (isset($sec['lp_holders']) && is_array($sec['lp_holders'])) {
            foreach ($sec['lp_holders'] as $h) {
                $isLocked = isset($h['is_locked']) && $h['is_locked'] == '1';
                $addr = strtolower($h['address'] ?? '');
                if ($isLocked || $addr === '0x0000000000000000000000000000000000000000' || $addr === '0x000000000000000000000000000000000000dead') {
                    $lpLocked = true;
                    $lpBurnedPct += floatval($h['percent'] ?? 0) * 100;
                }
            }
        }
        $checks['lp_locked'] = ['pass' => $lpLocked, 'label' => 'LP Locked/Burned', 'value' => round($lpBurnedPct).'% locked'];

        $buyTax = floatval($sec['buy_tax'] ?? 0) * 100;
        $checks['buy_tax'] = ['pass' => $buyTax < 8, 'label' => 'Buy Tax < 8%', 'value' => $buyTax.'%'];

        $sellTax = floatval($sec['sell_tax'] ?? 0) * 100;
        $checks['sell_tax'] = ['pass' => $sellTax < 8, 'label' => 'Sell Tax < 8%', 'value' => $sellTax.'%'];

        $isMintable = ($sec['is_mintable'] ?? '0') == '1';
        $checks['no_mint'] = ['pass' => !$isMintable, 'label' => 'No Mint Function', 'value' => $isMintable ? 'CAN MINT!' : 'No'];

        $hasBlacklist = ($sec['cannot_buy'] ?? '0') == '1' || ($sec['cannot_sell_all'] ?? '0') == '1' || ($sec['transfer_pausable'] ?? '0') == '1';
        $checks['no_blacklist'] = ['pass' => !$hasBlacklist, 'label' => 'No Blacklist', 'value' => $hasBlacklist ? 'Has restrictions' : 'Clean'];

        $owner = strtolower($sec['owner_address'] ?? '');
        $renounced = $owner === '' || $owner === '0x0000000000000000000000000000000000000000' || ($sec['can_take_back_ownership'] ?? '0') == '0';
        $checks['renounced'] = ['pass' => $renounced, 'label' => 'Owner Renounced', 'value' => $renounced ? 'Yes' : 'Owner active'];

        $topPct = 0;
        if (isset($sec['holders']) && is_array($sec['holders'])) {
            foreach ($sec['holders'] as $h) {
                $addr = strtolower($h['address'] ?? '');
                if (in_array($addr, ['0x0000000000000000000000000000000000000000', '0x000000000000000000000000000000000000dead', strtolower($coin['address'])])) continue;
                $pct = floatval($h['percent'] ?? 0) * 100;
                if ($pct > $topPct) $topPct = $pct;
                break;
            }
        }
        $checks['top_holder'] = ['pass' => $topPct < 15 && $topPct > 0, 'label' => 'Top Holder < 15%', 'value' => round($topPct, 1).'%'];

        $isHoneypot = ($sec['is_honeypot'] ?? '0') == '1';
        $checks['sell_sim'] = ['pass' => !$isHoneypot, 'label' => 'Sell Simulation OK', 'value' => $isHoneypot ? 'HONEYPOT!' : 'Passes'];
    } else {
        foreach (['lp_locked','buy_tax','sell_tax','no_mint','no_blacklist','renounced','top_holder','sell_sim'] as $key) {
            $checks[$key] = ['pass' => false, 'label' => ucwords(str_replace('_',' ',$key)), 'value' => 'No data'];
        }
    }

    $checks['active_buyers'] = ['pass' => $coin['buys_5m'] >= 10, 'label' => '10+ Buyers / 5min', 'value' => $coin['buys_5m'].' buys'];

    foreach ($checks as $c) {
        if ($c['pass']) $safetyScore++;
    }

    $coin['safety_checks'] = $checks;
    $coin['safety_score'] = $safetyScore;
    $coin['safety_total'] = $totalChecks;
    $coin['safety_grade'] = $safetyScore >= 9 ? 'SAFE' : ($safetyScore >= 7 ? 'OK' : ($safetyScore >= 5 ? 'RISKY' : 'DANGER'));
    $coin['safety_color'] = $safetyScore >= 9 ? '#00E676' : ($safetyScore >= 7 ? '#FFD32A' : ($safetyScore >= 5 ? '#FF9F43' : '#FF4757'));

    // Whales from Etherscan
    $url = "https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokentx&contractaddress={$coin['address']}&page=1&offset=100&sort=desc&apikey={$ETHERSCAN_KEY}";
    $data = fetchJson($url, 8);

    $walletActivity = [];
    if (isset($data['result']) && is_array($data['result'])) {
        foreach ($data['result'] as $tx) {
            $from = strtolower($tx['from'] ?? '');
            $to = strtolower($tx['to'] ?? '');
            $decimals = intval($tx['tokenDecimal'] ?? 18);
            $amount = floatval($tx['value'] ?? 0) / pow(10, $decimals);
            if ($amount < 1) continue;
            $timestamp = intval($tx['timeStamp'] ?? 0);

            if ($from === strtolower($coin['address'])) continue;
            if ($to === '0x0000000000000000000000000000000000000000') continue;
            if ($from === '0x0000000000000000000000000000000000000000') continue;

            foreach ([$to, $from] as $idx => $addr) {
                if (!$addr) continue;
                if (!isset($walletActivity[$addr])) {
                    $walletActivity[$addr] = ['bought'=>0,'sold'=>0,'count_buys'=>0,'count_sells'=>0,'last_time'=>0,'last_action'=>'','last_amount'=>0];
                }
                if ($idx === 0) {
                    $walletActivity[$addr]['bought'] += $amount;
                    $walletActivity[$addr]['count_buys']++;
                    if ($timestamp > $walletActivity[$addr]['last_time']) {
                        $walletActivity[$addr]['last_time'] = $timestamp;
                        $walletActivity[$addr]['last_action'] = 'buy';
                        $walletActivity[$addr]['last_amount'] = $amount;
                    }
                } else {
                    $walletActivity[$addr]['sold'] += $amount;
                    $walletActivity[$addr]['count_sells']++;
                    if ($timestamp > $walletActivity[$addr]['last_time']) {
                        $walletActivity[$addr]['last_time'] = $timestamp;
                        $walletActivity[$addr]['last_action'] = 'sell';
                        $walletActivity[$addr]['last_amount'] = $amount;
                    }
                }
            }
        }
    }

    $whaleList = [];
    foreach ($walletActivity as $addr => $act) {
        $netPosition = $act['bought'] - $act['sold'];
        $netUsd = $netPosition * $coin['price'];
        $totalBoughtUsd = $act['bought'] * $coin['price'];
        if ($totalBoughtUsd < 1000 && abs($netUsd) < 1000) continue;

        $type = $netPosition > 0 ? 'holding' : ($act['count_sells'] > $act['count_buys'] ? 'dumping' : 'flipping');
        $timeAgo = time() - $act['last_time'];

        $whaleList[] = [
            'wallet' => substr($addr, 0, 6) . '...' . substr($addr, -4),
            'wallet_full' => $addr,
            'net_amount' => $netPosition,
            'net_usd' => $netUsd,
            'net_usd_formatted' => abs($netUsd) >= 1e6 ? '$'.number_format(abs($netUsd)/1e6,2).'M' : '$'.number_format(abs($netUsd)/1e3,1).'K',
            'bought_usd' => $totalBoughtUsd,
            'bought_usd_formatted' => $totalBoughtUsd >= 1e6 ? '$'.number_format($totalBoughtUsd/1e6,2).'M' : '$'.number_format($totalBoughtUsd/1e3,1).'K',
            'count_buys' => $act['count_buys'],
            'count_sells' => $act['count_sells'],
            'last_action' => $act['last_action'],
            'time_ago' => $timeAgo,
            'time_display' => $timeAgo < 60 ? $timeAgo.'s' : ($timeAgo < 3600 ? floor($timeAgo/60).'m' : ($timeAgo < 86400 ? floor($timeAgo/3600).'h' : floor($timeAgo/86400).'d')),
            'type' => $type,
            'explorer_url' => "https://etherscan.io/address/{$addr}",
        ];
    }
    usort($whaleList, fn($a, $b) => abs($b['net_usd']) <=> abs($a['net_usd']));

    $coin['whales'] = array_slice($whaleList, 0, 10);
    $coin['total_whales'] = count($whaleList);
    $coin['holders_count'] = count(array_filter($whaleList, fn($w) => $w['net_amount'] > 0));
    $coin['dumpers_count'] = count(array_filter($whaleList, fn($w) => $w['count_sells'] > $w['count_buys']));

    usleep(300000);
}
unset($coin);

usort($newCoins, fn($a, $b) => ($b['safety_score']*10 + ($b['total_whales']??0)) - ($a['safety_score']*10 + ($a['total_whales']??0)));

$stats = [
    'total_coins' => count($newCoins),
    'safe_coins' => count(array_filter($newCoins, fn($c) => $c['safety_grade'] === 'SAFE')),
    'risky_coins' => count(array_filter($newCoins, fn($c) => in_array($c['safety_grade'], ['RISKY','DANGER']))),
    'total_whales' => array_sum(array_map(fn($c) => $c['total_whales']??0, $newCoins)),
    'total_holders' => array_sum(array_map(fn($c) => $c['holders_count']??0, $newCoins)),
    'total_dumpers' => array_sum(array_map(fn($c) => $c['dumpers_count']??0, $newCoins)),
];

$result = [
    'success' => true,
    'updated_at' => date('Y-m-d H:i:s'),
    'stats' => $stats,
    'coins' => $newCoins,
];

file_put_contents($cacheFile, json_encode($result));
echo json_encode($result);
