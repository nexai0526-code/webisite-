<?php
session_start();

// ===== CHANGE THIS PASSWORD =====
$ADMIN_PASSWORD = 'CallGod2026!';
// =================================

$dataFile = __DIR__ . '/data/calls.json';
if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0755, true);
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode(['calls'=>[],'stats'=>['believers'=>0],'settings'=>['next_call_hour'=>10,'timezone'=>'America/New_York']], JSON_PRETTY_PRINT));
}

// Login
if (isset($_POST['login'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Process actions
if (isset($_SESSION['admin']) && $_SESSION['admin']) {
    $data = json_decode(file_get_contents($dataFile), true);
    
    // Add new call
    if (isset($_POST['add_call'])) {
        $newCall = [
            'id' => uniqid(),
            'date' => $_POST['date'],
            'call_number' => count($data['calls']) + 1,
            'coin' => strtoupper(trim($_POST['coin'])),
            'direction' => $_POST['direction'],
            'entry_price' => floatval($_POST['entry_price']),
            'prediction' => $_POST['prediction'],
            'result' => 'pending',
            'exit_price' => null,
            'pnl' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $data['calls'][] = $newCall;
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        $msg = "Call added!";
    }
    
    // Update call result
    if (isset($_POST['update_result'])) {
        $callId = $_POST['call_id'];
        $exitPrice = floatval($_POST['exit_price']);
        foreach ($data['calls'] as &$call) {
            if ($call['id'] === $callId) {
                $call['exit_price'] = $exitPrice;
                $entryPrice = $call['entry_price'];
                if ($call['direction'] === 'bullish') {
                    $pnl = round((($exitPrice - $entryPrice) / $entryPrice) * 100, 1);
                } else {
                    $pnl = round((($entryPrice - $exitPrice) / $entryPrice) * 100, 1);
                }
                $call['pnl'] = $pnl;
                $call['result'] = $pnl > 0 ? 'win' : 'loss';
                break;
            }
        }
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        $msg = "Result updated!";
    }
    
    // Delete call
    if (isset($_POST['delete_call'])) {
        $callId = $_POST['call_id'];
        $data['calls'] = array_values(array_filter($data['calls'], fn($c) => $c['id'] !== $callId));
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        $msg = "Call deleted!";
    }
    
    // Update believers count
    if (isset($_POST['update_believers'])) {
        $data['stats']['believers'] = intval($_POST['believers']);
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        $msg = "Believers updated!";
    }
    
    $data = json_decode(file_get_contents($dataFile), true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CallGod Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0C0B09;color:#FAF6EF;font-family:'Outfit',sans-serif;padding:20px;max-width:900px;margin:0 auto}
h1{color:#C9A84C;font-size:24px;margin-bottom:24px;font-weight:400;letter-spacing:4px}
h2{color:#C9A84C;font-size:18px;margin:32px 0 16px;font-weight:400;letter-spacing:2px}
.msg{background:rgba(76,175,80,.15);border:1px solid #4CAF50;color:#4CAF50;padding:12px 20px;margin-bottom:20px;font-size:14px}
form{margin-bottom:24px}
label{display:block;font-size:12px;color:#8a8070;letter-spacing:2px;margin-bottom:6px;margin-top:16px}
input,select,textarea{width:100%;padding:12px;background:#121110;border:1px solid rgba(201,168,76,.2);color:#FAF6EF;font-family:inherit;font-size:14px;outline:none}
input:focus,select:focus,textarea:focus{border-color:#C9A84C}
textarea{height:100px;resize:vertical}
select{appearance:none}
button,.btn{background:#C9A84C;color:#0C0B09;border:none;padding:12px 28px;font-family:inherit;font-size:14px;cursor:pointer;letter-spacing:2px;margin-top:16px;transition:opacity .3s}
button:hover,.btn:hover{opacity:.8}
.btn-danger{background:#FF5252}
.btn-small{padding:8px 16px;font-size:12px;margin:0}
table{width:100%;border-collapse:collapse;margin-top:16px}
th{text-align:left;font-size:11px;color:#8a8070;letter-spacing:2px;padding:12px 8px;border-bottom:1px solid rgba(201,168,76,.1)}
td{padding:12px 8px;border-bottom:1px solid rgba(201,168,76,.05);font-size:13px}
.win{color:#4CAF50}.loss{color:#FF5252}.pending{color:#C9A84C}
.login-box{max-width:400px;margin:100px auto;text-align:center}
.login-box h1{margin-bottom:32px}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
.stat-box{background:#121110;border:1px solid rgba(201,168,76,.1);padding:20px;text-align:center}
.stat-box .val{font-size:28px;color:#C9A84C;font-weight:300}
.stat-box .lbl{font-size:10px;color:#8a8070;letter-spacing:2px;margin-top:4px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
a{color:#C9A84C}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;padding-bottom:16px;border-bottom:1px solid rgba(201,168,76,.1)}
</style>
</head>
<body>

<?php if (!isset($_SESSION['admin']) || !$_SESSION['admin']): ?>
<div class="login-box">
    <h1>CALLGOD ADMIN</h1>
    <form method="POST">
        <label>PASSWORD</label>
        <input type="password" name="password" required placeholder="Enter admin password">
        <button type="submit" name="login">ENTER TEMPLE</button>
    </form>
</div>

<?php else: ?>
<?php
$calls = $data['calls'] ?? [];
$wins = count(array_filter($calls, fn($c) => ($c['result']??'') === 'win'));
$losses = count(array_filter($calls, fn($c) => ($c['result']??'') === 'loss'));
$pending = count(array_filter($calls, fn($c) => ($c['result']??'') === 'pending'));
$total = count($calls);
$winRate = $total > 0 ? round(($wins / max($total - $pending, 1)) * 100) : 0;
?>

<div class="topbar">
    <h1>CALLGOD ADMIN</h1>
    <div><a href="/">View Site</a> &nbsp;|&nbsp; <a href="?logout">Logout</a></div>
</div>

<?php if (isset($msg)): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

<div class="stats-grid">
    <div class="stat-box"><div class="val"><?= $total ?></div><div class="lbl">TOTAL CALLS</div></div>
    <div class="stat-box"><div class="val"><?= $winRate ?>%</div><div class="lbl">WIN RATE</div></div>
    <div class="stat-box"><div class="val"><?= $wins ?>W / <?= $losses ?>L</div><div class="lbl">RECORD</div></div>
    <div class="stat-box"><div class="val"><?= $data['stats']['believers'] ?? 0 ?></div><div class="lbl">BELIEVERS</div></div>
</div>

<form method="POST" style="display:inline">
    <label>UPDATE BELIEVERS COUNT</label>
    <div style="display:flex;gap:8px">
        <input type="number" name="believers" value="<?= $data['stats']['believers'] ?? 0 ?>" style="width:200px">
        <button type="submit" name="update_believers" class="btn-small" style="margin:0">UPDATE</button>
    </div>
</form>

<h2>ADD NEW CALL</h2>
<form method="POST">
    <div class="row">
        <div><label>DATE</label><input type="date" name="date" value="<?= date('Y-m-d') ?>" required></div>
        <div><label>COIN (e.g. $SOL)</label><input type="text" name="coin" placeholder="$SOL" required></div>
    </div>
    <div class="row">
        <div><label>DIRECTION</label>
            <select name="direction">
                <option value="bullish">BULLISH ▲</option>
                <option value="bearish">BEARISH ▼</option>
            </select>
        </div>
        <div><label>ENTRY PRICE ($)</label><input type="number" step="0.0001" name="entry_price" placeholder="172.40" required></div>
    </div>
    <label>PROPHECY TEXT</label>
    <textarea name="prediction" placeholder="The Oracle sees strength in Solana. Whale wallets accumulating. God has spoken." required></textarea>
    <button type="submit" name="add_call">PUBLISH PROPHECY</button>
</form>

<h2>ALL CALLS</h2>
<table>
    <tr><th>#</th><th>DATE</th><th>COIN</th><th>DIRECTION</th><th>ENTRY</th><th>EXIT</th><th>P&L</th><th>RESULT</th><th>ACTION</th></tr>
    <?php foreach (array_reverse($calls) as $call): ?>
    <tr>
        <td><?= $call['call_number'] ?? '-' ?></td>
        <td><?= $call['date'] ?></td>
        <td><strong><?= $call['coin'] ?></strong></td>
        <td><?= $call['direction'] === 'bullish' ? '▲ BULL' : '▼ BEAR' ?></td>
        <td>$<?= number_format($call['entry_price'], 2) ?></td>
        <td><?= $call['exit_price'] ? '$'.number_format($call['exit_price'], 2) : '-' ?></td>
        <td class="<?= ($call['result']??'') ?>"><?= $call['pnl'] !== null ? ($call['pnl'] > 0 ? '+' : '').$call['pnl'].'%' : '-' ?></td>
        <td class="<?= ($call['result']??'') ?>"><?= strtoupper($call['result'] ?? 'pending') ?></td>
        <td>
            <?php if (($call['result']??'') === 'pending'): ?>
            <form method="POST" style="display:flex;gap:4px;margin:0">
                <input type="hidden" name="call_id" value="<?= $call['id'] ?>">
                <input type="number" step="0.0001" name="exit_price" placeholder="Exit $" style="width:90px;padding:6px" required>
                <button type="submit" name="update_result" class="btn-small" style="background:#4CAF50">SET</button>
            </form>
            <?php endif; ?>
            <form method="POST" style="display:inline;margin:0">
                <input type="hidden" name="call_id" value="<?= $call['id'] ?>">
                <button type="submit" name="delete_call" class="btn-small btn-danger" onclick="return confirm('Delete?')">DEL</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php endif; ?>
</body>
</html>