<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dataFile = __DIR__ . '/data/calls.json';

// Ensure data directory exists
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Initialize data file if not exists
if (!file_exists($dataFile)) {
    $initial = [
        'calls' => [],
        'stats' => ['total_calls' => 0, 'wins' => 0, 'losses' => 0, 'pending' => 0, 'believers' => 0],
        'settings' => ['next_call_hour' => 10, 'timezone' => 'America/New_York']
    ];
    file_put_contents($dataFile, json_encode($initial, JSON_PRETTY_PRINT));
}

$data = json_decode(file_get_contents($dataFile), true);

$action = $_GET['action'] ?? 'get_all';

switch ($action) {
    case 'get_all':
        // Calculate stats
        $calls = $data['calls'] ?? [];
        $wins = count(array_filter($calls, fn($c) => ($c['result'] ?? '') === 'win'));
        $losses = count(array_filter($calls, fn($c) => ($c['result'] ?? '') === 'loss'));
        $pending = count(array_filter($calls, fn($c) => ($c['result'] ?? '') === 'pending'));
        $total = count($calls);
        $winRate = $total > 0 ? round(($wins / max($total - $pending, 1)) * 100) : 0;
        
        // Current streak
        $streak = 0;
        $streakType = '';
        foreach (array_reverse($calls) as $c) {
            if (($c['result'] ?? '') === 'pending') continue;
            if ($streakType === '') $streakType = $c['result'] ?? '';
            if (($c['result'] ?? '') === $streakType) $streak++;
            else break;
        }
        
        $latestCall = !empty($calls) ? end($calls) : null;
        
        echo json_encode([
            'success' => true,
            'latest_call' => $latestCall,
            'recent_calls' => array_slice(array_reverse($calls), 0, 8),
            'stats' => [
                'total_calls' => $total,
                'wins' => $wins,
                'losses' => $losses,
                'win_rate' => $winRate,
                'streak' => $streak,
                'streak_type' => $streakType === 'win' ? 'W' : 'L',
                'believers' => $data['stats']['believers'] ?? 0
            ],
            'settings' => $data['settings'] ?? []
        ]);
        break;
        
    case 'get_latest':
        $calls = $data['calls'] ?? [];
        $latest = !empty($calls) ? end($calls) : null;
        echo json_encode(['success' => true, 'call' => $latest]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>