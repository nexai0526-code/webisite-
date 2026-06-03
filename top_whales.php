<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$f = __DIR__ . '/data/top_whales.json';
if (!file_exists($f)) {
    echo json_encode(['success'=>false,'error'=>'not scanned yet','top'=>[]]);
    exit;
}
echo file_get_contents($f);
