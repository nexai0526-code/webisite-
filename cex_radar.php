<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$f = __DIR__ . '/data/cex_radar.json';
if (!file_exists($f)) { echo json_encode(['success'=>false,'error'=>'not scanned yet','coins'=>[]]); exit; }
echo file_get_contents($f);
