<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$file = __DIR__ . '/data/discovered_whales.json';
if (!file_exists($file)) { echo json_encode(['success'=>false,'whales'=>[]]); exit; }
$d = json_decode(file_get_contents($file), true);
$whales = [];
foreach ($d as $addr => $info) {
    $whales[] = [
        'address' => $addr,
        'label' => $info['label'] ?? 'Discovered',
        'score' => $info['score'] ?? 100,
        'coins_count' => count($info['coins_caught'] ?? []),
        'last_seen' => $info['last_seen'] ?? 0,
    ];
}
usort($whales, fn($a,$b) => $b['coins_count'] <=> $a['coins_count']);
echo json_encode([
    'success' => true,
    'total' => count($whales),
    'whales' => array_slice($whales, 0, 100),
]);
