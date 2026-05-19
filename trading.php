<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Auth check - same as admin panel
$ADMIN_PASS = 'CallGod2026!';
if (!isset($_SESSION['admin_auth'])) {
    if (isset($_POST['password']) && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin_auth'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html><head><title>Login - Trading Panel</title>
        <style>
        body{background:#08080F;color:#F0F0F8;font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
        .box{background:#12121E;border:1px solid rgba(255,107,107,.2);border-radius:14px;padding:40px;width:340px}
        h2{font-family:'Syne',sans-serif;margin-bottom:24px;background:linear-gradient(135deg,#FF6B6B,#FFD32A,#00E676,#00D2FF,#7C4DFF);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        input{width:100%;padding:14px;background:#0B0B13;border:1px solid rgba(255,255,255,.08);border-radius:8px;color:#fff;font-size:14px;margin-bottom:14px}
        button{width:100%;padding:14px;background:linear-gradient(135deg,#FF6B6B,#FFD32A,#00E676,#00D2FF,#7C4DFF);border:none;color:#fff;font-weight:600;border-radius:8px;cursor:pointer}
        </style></head><body>
        <form class="box" method="POST">
            <h2>🐋 Trading Panel</h2>
            <input type="password" name="password" placeholder="Admin Password" autofocus>
            <button>Enter</button>
        </form></body></html>
        <?php
        exit;
    }
}

// Load tracking data
$dataFile = __DIR__ . '/data/trading_panel.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);
$tracking = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : ['watchlist' => [], 'positions' => [], 'alerts' => []];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_watch') {
            $tracking['watchlist'][] = [
                'id' => uniqid(),
                'symbol' => strtoupper($_POST['symbol']),
                'token_address' => $_POST['token_address'] ?? '',
                'note' => $_POST['note'] ?? '',
                'added_at' => time(),
                'entry_price' => floatval($_POST['entry_price'] ?? 0),
            ];
            file_put_contents($dataFile, json_encode($tracking));
        }
        if ($_POST['action'] === 'add_position') {
            $tracking['positions'][] = [
                'id' => uniqid(),
                'symbol' => strtoupper($_POST['symbol']),
                'type' => $_POST['type'],
                'entry_price' => floatval($_POST['entry_price']),
                'amount_usd' => floatval($_POST['amount_usd']),
                'token_amount' => floatval($_POST['token_amount'] ?? 0),
                'target_price' => floatval($_POST['target_price'] ?? 0),
                'stop_loss' => floatval($_POST['stop_loss'] ?? 0),
                'note' => $_POST['note'] ?? '',
                'opened_at' => time(),
                'status' => 'open',
            ];
            file_put_contents($dataFile, json_encode($tracking));
        }
        if ($_POST['action'] === 'close_position' && isset($_POST['id'])) {
            foreach ($tracking['positions'] as &$p) {
                if ($p['id'] === $_POST['id']) {
                    $p['status'] = 'closed';
                    $p['exit_price'] = floatval($_POST['exit_price']);
                    $p['closed_at'] = time();
                    $p['pnl'] = (($p['exit_price'] - $p['entry_price']) / $p['entry_price']) * 100;
                }
            }
            file_put_contents($dataFile, json_encode($tracking));
        }
        if ($_POST['action'] === 'remove_watch' && isset($_POST['id'])) {
            $tracking['watchlist'] = array_filter($tracking['watchlist'], fn($w) => $w['id'] !== $_POST['id']);
            $tracking['watchlist'] = array_values($tracking['watchlist']);
            file_put_contents($dataFile, json_encode($tracking));
        }
        if ($_POST['action'] === 'logout') {
            session_destroy();
            header('Location: /trading.php');
            exit;
        }
        header('Location: /trading.php');
        exit;
    }
}

// Calculate stats
$openPositions = array_filter($tracking['positions'], fn($p) => $p['status'] === 'open');
$closedPositions = array_filter($tracking['positions'], fn($p) => $p['status'] === 'closed');
$totalPnl = 0;
$wins = 0; $losses = 0;
foreach ($closedPositions as $p) {
    $pnl = $p['pnl'] ?? 0;
    $totalPnl += $pnl;
    if ($pnl > 0) $wins++; else $losses++;
}
$winRate = ($wins + $losses) > 0 ? round(($wins / ($wins + $losses)) * 100) : 0;
$totalCapital = array_sum(array_column($openPositions, 'amount_usd'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Trading Panel - NexAI</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600;700;800&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
--c1:#FF6B6B;--c4:#00E676;--c5:#00D2FF;--c6:#7C4DFF;--c3:#FFD32A;--red:#FF4757;
--bg:#08080F;--card:#12121E;--card2:#181828;--text:#F0F0F8;--text2:#9B9BB0;--text3:#5B5B72;
--border:rgba(255,255,255,.05);
--rainbow:linear-gradient(135deg,#FF6B6B,#FF9F43,#FFD32A,#00E676,#00D2FF,#7C4DFF);
}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;line-height:1.5;padding:20px}
.container{max-width:1400px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;background:var(--card);border:1px solid var(--border);border-radius:14px;margin-bottom:20px}
.header h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;display:flex;align-items:center;gap:12px}
.logo-icon{width:36px;height:36px;border-radius:10px;background:var(--rainbow);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#fff}
.header-links{display:flex;gap:10px;align-items:center}
.header-links a,.header-links button{padding:8px 16px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text2);font-size:12px;text-decoration:none;font-family:'JetBrains Mono',monospace;letter-spacing:1px;cursor:pointer;transition:all .2s}
.header-links a:hover,.header-links button:hover{color:var(--c1);border-color:rgba(255,107,107,.3)}
.logout{background:rgba(255,71,87,.08)!important;color:var(--red)!important;border-color:rgba(255,71,87,.2)!important}

/* Stats */
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px}
.s-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 20px;position:relative;overflow:hidden}
.s-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.s-card.c1::after{background:var(--rainbow)}
.s-card.c2::after{background:var(--c4)}
.s-card.c3::after{background:var(--c5)}
.s-card.c4::after{background:var(--c3)}
.s-card.c5::after{background:var(--c6)}
.s-lab{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);letter-spacing:1.5px;margin-bottom:8px}
.s-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:700}
.s-val.green{color:var(--c4)}
.s-val.red{color:var(--red)}
.s-val.amber{color:var(--c3)}
.s-val.cyan{color:var(--c5)}
.s-val.purple{color:var(--c6)}
.s-sub{font-size:11px;color:var(--text3);margin-top:4px}

/* Grid layout */
.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.full{grid-column:1/-1}

/* Panels */
.panel{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden}
.p-head{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.p-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:600;display:flex;align-items:center;gap:8px}
.p-title .dot{width:8px;height:8px;border-radius:50%;background:var(--c4);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.p-body{padding:18px 22px}

/* Forms */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}
.form-full{grid-template-columns:1fr}
.field label{display:block;font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);letter-spacing:1.5px;margin-bottom:6px}
.field input,.field select,.field textarea{width:100%;padding:10px 12px;background:#0B0B13;border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;font-family:'Inter',sans-serif}
.field input:focus,.field select:focus,.field textarea:focus{outline:none;border-color:var(--c1)}
.btn{padding:12px 20px;background:var(--rainbow);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;width:100%;margin-top:10px;transition:opacity .2s}
.btn:hover{opacity:.9}
.btn-sm{padding:6px 12px;font-size:11px;width:auto;margin-top:0}
.btn-danger{background:rgba(255,71,87,.15);color:var(--red);border:1px solid rgba(255,71,87,.3)}

/* Lists */
.item{display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;padding:14px 22px;border-bottom:1px solid rgba(255,255,255,.02);transition:background .15s}
.item:hover{background:rgba(255,255,255,.02)}
.item-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600}
.item-icon.buy{background:rgba(0,230,118,.1);color:var(--c4)}
.item-icon.sell{background:rgba(255,71,87,.1);color:var(--red)}
.item-icon.watch{background:rgba(0,210,255,.1);color:var(--c5)}
.item-info .symbol{font-family:'Syne',sans-serif;font-size:15px;font-weight:600;margin-bottom:2px}
.item-info .meta{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3)}
.item-info .note{font-size:11px;color:var(--text2);margin-top:3px;font-style:italic}
.item-actions{display:flex;gap:6px}
.item-value{text-align:right}
.item-value .v{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500}
.item-value .l{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);margin-top:2px}
.pnl-pos{color:var(--c4)}
.pnl-neg{color:var(--red)}

.empty{padding:40px 22px;text-align:center;color:var(--text3);font-size:13px}

/* Tabs for live data */
.live-data{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:20px}

/* Quick add buttons */
.quick-add{display:flex;gap:8px;flex-wrap:wrap;padding:14px 22px;background:rgba(255,255,255,.02);border-bottom:1px solid var(--border)}
.quick-btn{padding:6px 14px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;color:var(--text2);font-family:'JetBrains Mono',monospace;font-size:11px;cursor:pointer;transition:all .2s}
.quick-btn:hover{border-color:var(--c1);color:var(--c1)}

/* Live feed */
.live-feed{max-height:600px;overflow-y:auto}
.live-feed::-webkit-scrollbar{width:4px}
.live-feed::-webkit-scrollbar-thumb{background:rgba(255,107,107,.2);border-radius:2px}

@media(max-width:900px){
  .stats{grid-template-columns:repeat(2,1fr)}
  .grid{grid-template-columns:1fr}
  .live-data{grid-template-columns:1fr}
  .form-row{grid-template-columns:1fr}
  .header{flex-direction:column;gap:12px;text-align:center}
}
</style>
</head>
<body>

<div class="container">

  <div class="header">
    <h1>
      <div class="logo-icon">N</div>
      Trading Panel
    </h1>
    <div class="header-links">
      <a href="/whale.html" target="_blank">🐋 Public Tracker</a>
      <a href="/admin.php">🔮 Oracle Admin</a>
      <a href="/">CallGod</a>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="logout">
        <button class="logout">Logout</button>
      </form>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats">
    <div class="s-card c1">
      <div class="s-lab">OPEN POSITIONS</div>
      <div class="s-val"><?= count($openPositions) ?></div>
      <div class="s-sub">Active trades</div>
    </div>
    <div class="s-card c2">
      <div class="s-lab">TOTAL CAPITAL</div>
      <div class="s-val green">$<?= number_format($totalCapital, 0) ?></div>
      <div class="s-sub">Deployed</div>
    </div>
    <div class="s-card c3">
      <div class="s-lab">TOTAL PNL</div>
      <div class="s-val <?= $totalPnl >= 0 ? 'green' : 'red' ?>"><?= $totalPnl >= 0 ? '+' : '' ?><?= number_format($totalPnl, 1) ?>%</div>
      <div class="s-sub"><?= count($closedPositions) ?> closed</div>
    </div>
    <div class="s-card c4">
      <div class="s-lab">WIN RATE</div>
      <div class="s-val amber"><?= $winRate ?>%</div>
      <div class="s-sub"><?= $wins ?>W / <?= $losses ?>L</div>
    </div>
    <div class="s-card c5">
      <div class="s-lab">WATCHLIST</div>
      <div class="s-val purple"><?= count($tracking['watchlist']) ?></div>
      <div class="s-sub">Coins tracked</div>
    </div>
  </div>

  <!-- LIVE DATA from APIs -->
  <div class="live-data">
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div>🔥 Hot New Launches</div>
        <a href="/whale.html" target="_blank" style="font-size:10px;color:var(--c1);text-decoration:none">View All →</a>
      </div>
      <div class="live-feed" id="hotLaunches">
        <div class="empty">Loading...</div>
      </div>
    </div>

    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div>🐋 Whale Activity</div>
        <span style="font-size:10px;color:var(--text3);font-family:monospace">REAL-TIME</span>
      </div>
      <div class="live-feed" id="whaleActivity">
        <div class="empty">Loading...</div>
      </div>
    </div>

    <div class="panel">
      <div class="p-head">
        <div class="p-title">🚀 Top Gainers 24H</div>
        <span style="font-size:10px;color:var(--text3);font-family:monospace">MARKET</span>
      </div>
      <div class="live-feed" id="topGainers">
        <div class="empty">Loading...</div>
      </div>
    </div>
  </div>

  <!-- Add Position + Add to Watchlist -->
  <div class="grid">

    <!-- Add Position -->
    <div class="panel">
      <div class="p-head">
        <div class="p-title">📈 Add New Position</div>
      </div>
      <div class="p-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_position">
          <div class="form-row">
            <div class="field">
              <label>SYMBOL</label>
              <input type="text" name="symbol" placeholder="e.g. SOL, BONK" required>
            </div>
            <div class="field">
              <label>TYPE</label>
              <select name="type">
                <option value="buy">BUY / LONG</option>
                <option value="sell">SELL / SHORT</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label>ENTRY PRICE ($)</label>
              <input type="number" name="entry_price" step="0.000000001" required>
            </div>
            <div class="field">
              <label>AMOUNT (USD)</label>
              <input type="number" name="amount_usd" step="0.01" required>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label>TARGET PRICE</label>
              <input type="number" name="target_price" step="0.000000001">
            </div>
            <div class="field">
              <label>STOP LOSS</label>
              <input type="number" name="stop_loss" step="0.000000001">
            </div>
          </div>
          <div class="field" style="margin-bottom:10px">
            <label>NOTE</label>
            <input type="text" name="note" placeholder="Why this trade? Whale signal? Pump.fun launch?">
          </div>
          <button class="btn">+ Open Position</button>
        </form>
      </div>
    </div>

    <!-- Add to Watchlist -->
    <div class="panel">
      <div class="p-head">
        <div class="p-title">👁️ Add to Watchlist</div>
      </div>
      <div class="p-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_watch">
          <div class="form-row form-full">
            <div class="field">
              <label>SYMBOL</label>
              <input type="text" name="symbol" placeholder="e.g. WIF, PEPE" required>
            </div>
          </div>
          <div class="form-row form-full">
            <div class="field">
              <label>TOKEN ADDRESS (optional)</label>
              <input type="text" name="token_address" placeholder="Solana mint address">
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label>ENTRY PRICE (optional)</label>
              <input type="number" name="entry_price" step="0.000000001">
            </div>
            <div class="field">
              <label>QUICK NOTE</label>
              <input type="text" name="note" placeholder="Why watching?">
            </div>
          </div>
          <button class="btn">+ Add to Watchlist</button>
        </form>
      </div>
    </div>

  </div>

  <!-- Open Positions -->
  <div class="panel" style="margin-bottom:20px">
    <div class="p-head">
      <div class="p-title">📊 Open Positions (<?= count($openPositions) ?>)</div>
      <span style="font-size:10px;color:var(--text3);font-family:monospace">LIVE TRACKING</span>
    </div>
    <?php if (empty($openPositions)): ?>
      <div class="empty">No open positions. Add one above to start tracking.</div>
    <?php else: ?>
      <?php foreach (array_reverse($openPositions) as $p): 
        $age = time() - $p['opened_at'];
        $ageStr = $age < 3600 ? floor($age/60).'m' : ($age < 86400 ? floor($age/3600).'h' : floor($age/86400).'d');
      ?>
        <div class="item">
          <div class="item-icon <?= $p['type'] ?>"><?= $p['type'] === 'buy' ? '↑' : '↓' ?></div>
          <div class="item-info">
            <div class="symbol">$<?= htmlspecialchars($p['symbol']) ?> <span style="font-size:10px;color:var(--text3);font-family:monospace;letter-spacing:1px">· <?= strtoupper($p['type']) ?></span></div>
            <div class="meta">Entry: $<?= $p['entry_price'] ?> · Size: $<?= number_format($p['amount_usd'], 0) ?> · <?= $ageStr ?> ago<?php if($p['target_price']): ?> · TP: $<?= $p['target_price'] ?><?php endif; ?><?php if($p['stop_loss']): ?> · SL: $<?= $p['stop_loss'] ?><?php endif; ?></div>
            <?php if ($p['note']): ?><div class="note">"<?= htmlspecialchars($p['note']) ?>"</div><?php endif; ?>
          </div>
          <div class="item-actions">
            <form method="POST" style="display:flex;gap:6px;align-items:center" onsubmit="return confirm('Close position at this price?')">
              <input type="hidden" name="action" value="close_position">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <input type="number" name="exit_price" step="0.000000001" placeholder="Exit $" required style="width:90px;padding:6px 8px;background:#0B0B13;border:1px solid var(--border);border-radius:6px;color:#fff;font-size:11px">
              <button class="btn btn-sm">Close</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Watchlist -->
  <div class="panel" style="margin-bottom:20px">
    <div class="p-head">
      <div class="p-title">👁️ Watchlist (<?= count($tracking['watchlist']) ?>)</div>
    </div>
    <?php if (empty($tracking['watchlist'])): ?>
      <div class="empty">Watchlist empty. Add coins you want to monitor.</div>
    <?php else: ?>
      <?php foreach (array_reverse($tracking['watchlist']) as $w): 
        $age = time() - $w['added_at'];
        $ageStr = $age < 3600 ? floor($age/60).'m' : ($age < 86400 ? floor($age/3600).'h' : floor($age/86400).'d');
      ?>
        <div class="item">
          <div class="item-icon watch">👁</div>
          <div class="item-info">
            <div class="symbol">$<?= htmlspecialchars($w['symbol']) ?></div>
            <div class="meta">Added <?= $ageStr ?> ago<?php if($w['entry_price']): ?> · Entry: $<?= $w['entry_price'] ?><?php endif; ?></div>
            <?php if ($w['note']): ?><div class="note">"<?= htmlspecialchars($w['note']) ?>"</div><?php endif; ?>
          </div>
          <div class="item-actions">
            <form method="POST" onsubmit="return confirm('Remove from watchlist?')">
              <input type="hidden" name="action" value="remove_watch">
              <input type="hidden" name="id" value="<?= $w['id'] ?>">
              <button class="btn btn-sm btn-danger">Remove</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Closed Positions History -->
  <?php if (!empty($closedPositions)): ?>
  <div class="panel">
    <div class="p-head">
      <div class="p-title">📜 Trade History (<?= count($closedPositions) ?>)</div>
      <span style="font-size:10px;color:var(--text3);font-family:monospace">CLOSED TRADES</span>
    </div>
    <?php foreach (array_slice(array_reverse($closedPositions), 0, 20) as $p): 
      $pnl = $p['pnl'] ?? 0;
    ?>
      <div class="item">
        <div class="item-icon <?= $pnl >= 0 ? 'buy' : 'sell' ?>"><?= $pnl >= 0 ? '✓' : '✗' ?></div>
        <div class="item-info">
          <div class="symbol">$<?= htmlspecialchars($p['symbol']) ?> <span style="font-size:10px;color:var(--text3);font-family:monospace;letter-spacing:1px">· <?= strtoupper($p['type']) ?></span></div>
          <div class="meta">$<?= $p['entry_price'] ?> → $<?= $p['exit_price'] ?? '-' ?> · Size: $<?= number_format($p['amount_usd'], 0) ?></div>
          <?php if ($p['note']): ?><div class="note">"<?= htmlspecialchars($p['note']) ?>"</div><?php endif; ?>
        </div>
        <div class="item-value">
          <div class="v <?= $pnl >= 0 ? 'pnl-pos' : 'pnl-neg' ?>"><?= $pnl >= 0 ? '+' : '' ?><?= number_format($pnl, 2) ?>%</div>
          <div class="l">$<?= number_format(($p['amount_usd'] * $pnl / 100), 0) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<script>
// Load hot launches
async function loadHotLaunches(){
  try{
    const r=await fetch('/pump_whales.php');
    const d=await r.json();
    if(!d.success||!d.tokens) return;
    const el=document.getElementById('hotLaunches');
    let h='';
    d.tokens.slice(0,8).forEach(t=>{
      const heatColor={FIRE:'#FF4757',HOT:'#FF9F43',WARM:'#FFD32A',COLD:'#9B9BB0'}[t.whale_heat];
      const c1h=t.change_1h;
      h+=`<a href="${t.dex_url}" target="_blank" style="display:block;padding:12px 22px;border-bottom:1px solid rgba(255,255,255,.02);text-decoration:none;color:#F0F0F8">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
          <div>
            <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600">${t.symbol} <span style="font-size:9px;padding:2px 6px;border-radius:4px;background:rgba(255,71,87,.15);color:${heatColor};letter-spacing:1px;font-family:monospace">${t.whale_heat}</span></div>
            <div style="font-size:10px;color:#5B5B72;font-family:monospace;margin-top:2px">${t.age_display} · vol:${t.volume_formatted}</div>
          </div>
          <div style="text-align:right">
            <div style="font-family:monospace;font-size:11px">${t.price_formatted}</div>
            <div style="font-family:monospace;font-size:11px;color:${c1h>=0?'#00E676':'#FF4757'};margin-top:2px">${c1h>=0?'+':''}${c1h}%</div>
          </div>
        </div>
      </a>`;
    });
    el.innerHTML=h;
  }catch(e){console.log('Hot launches loading...')}
}

// Load whale activity
async function loadWhales(){
  try{
    const r=await fetch('/whale_real.php');
    const d=await r.json();
    if(!d.success||!d.coins) return;
    const el=document.getElementById('whaleActivity');
    const active=d.coins.filter(c=>c.buys_1h>0||c.sells_1h>0).slice(0,8);
    let h='';
    active.forEach(c=>{
      const isBuy=c.buys_1h>c.sells_1h;
      h+=`<a href="${c.url}" target="_blank" style="display:block;padding:12px 22px;border-bottom:1px solid rgba(255,255,255,.02);text-decoration:none;color:#F0F0F8">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
          <div>
            <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600">${c.symbol} <span style="font-size:9px;padding:2px 6px;border-radius:4px;background:${isBuy?'rgba(0,230,118,.1)':'rgba(255,71,87,.1)'};color:${isBuy?'#00E676':'#FF4757'};font-family:monospace">${isBuy?'BUYING':'SELLING'}</span></div>
            <div style="font-size:10px;color:#5B5B72;font-family:monospace;margin-top:2px">${c.buys_1h}🟢 / ${c.sells_1h}🔴 · ${c.volume_formatted}</div>
          </div>
          <div style="text-align:right">
            <div style="font-family:monospace;font-size:11px">${c.price_formatted}</div>
            <div style="font-family:monospace;font-size:11px;color:${c.change_1h>=0?'#00E676':'#FF4757'};margin-top:2px">${c.change_1h>=0?'+':''}${c.change_1h}%</div>
          </div>
        </div>
      </a>`;
    });
    el.innerHTML=h||'<div class="empty">No active whale trades right now</div>';
  }catch(e){console.log('Whale loading...')}
}

// Load top gainers
async function loadGainers(){
  try{
    const r=await fetch('/mega_data.php');
    const d=await r.json();
    if(!d.success||!d.top_gainers_24h) return;
    const el=document.getElementById('topGainers');
    let h='';
    d.top_gainers_24h.slice(0,8).forEach((c,i)=>{
      h+=`<div style="display:flex;align-items:center;gap:10px;padding:12px 22px;border-bottom:1px solid rgba(255,255,255,.02)">
        <span style="font-family:monospace;font-size:10px;color:#5B5B72;width:20px">#${i+1}</span>
        ${c.image?`<img src="${c.image}" style="width:24px;height:24px;border-radius:50%" onerror="this.style.display='none'">`:''}
        <div style="flex:1">
          <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600">${c.symbol}</div>
          <div style="font-size:10px;color:#5B5B72;font-family:monospace">${c.price_formatted} · vol:${c.volume}</div>
        </div>
        <div style="font-family:monospace;font-size:13px;color:#00E676;font-weight:500">+${c.change_24h}%</div>
      </div>`;
    });
    el.innerHTML=h;
  }catch(e){console.log('Gainers loading...')}
}

loadHotLaunches();
loadWhales();
loadGainers();
setInterval(loadHotLaunches, 90000);
setInterval(loadWhales, 120000);
setInterval(loadGainers, 120000);
</script>

</body>
</html>