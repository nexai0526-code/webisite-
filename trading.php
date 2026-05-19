<?php
session_start();
$PASSWORD = 'CallGod2026!';

if (isset($_POST['password'])) {
    if ($_POST['password'] === $PASSWORD) {
        $_SESSION['trading_auth'] = true;
        header('Location: trading.php');
        exit;
    } else {
        $error = 'Wrong password';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: trading.php');
    exit;
}

$isLoggedIn = isset($_SESSION['trading_auth']) && $_SESSION['trading_auth'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NexAI Trading Panel · Whale Copy Trader</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  :root {
    --c1:#FF6B6B; --c2:#FF9F43; --c3:#FFD32A; --c4:#00E676; --c5:#00D2FF; --c6:#7C4DFF;
    --bg:#05060e; --panel:#0c0e1c; --panel2:#11142a; --line:rgba(255,255,255,.08);
    --txt:#eef0ff; --mute:#8b91b8; --green:#00E676; --red:#ff5470;
  }
  body {
    font-family: -apple-system, "Segoe UI", Roboto, Inter, sans-serif;
    background: var(--bg); color: var(--txt); min-height:100vh;
    background-image:
      radial-gradient(circle at 10% 10%, rgba(255,107,107,.10), transparent 40%),
      radial-gradient(circle at 90% 20%, rgba(0,210,255,.10), transparent 40%),
      radial-gradient(circle at 50% 90%, rgba(124,77,255,.10), transparent 40%);
  }
  a { color: inherit; text-decoration: none; }
  .rainbow {
    background: linear-gradient(90deg,var(--c1),var(--c2),var(--c3),var(--c4),var(--c5),var(--c6));
    -webkit-background-clip:text; background-clip:text; color:transparent;
  }
  .rainbow-bg { background: linear-gradient(90deg,var(--c1),var(--c2),var(--c3),var(--c4),var(--c5),var(--c6)); }

  /* LOGIN */
  .login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
  .login-card {
    background: var(--panel); border:1px solid var(--line); border-radius:20px;
    padding:40px 30px; width:100%; max-width:380px; text-align:center;
  }
  .login-card h1 { font-size:28px; margin-bottom:8px; }
  .login-card p { color:var(--mute); margin-bottom:28px; font-size:13px; }
  .login-card input {
    width:100%; padding:14px 16px; border-radius:12px; border:1px solid var(--line);
    background:#080a18; color:var(--txt); font-size:15px; margin-bottom:14px; outline:none;
  }
  .login-card input:focus { border-color: var(--c5); }
  .login-card button {
    width:100%; padding:14px; border-radius:12px; border:none; cursor:pointer;
    background: linear-gradient(90deg,var(--c1),var(--c5),var(--c6));
    color:white; font-weight:700; font-size:15px; letter-spacing:.5px;
  }
  .err { color:var(--red); font-size:13px; margin-bottom:14px; }

  /* HEADER */
  .topbar {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 22px; border-bottom:1px solid var(--line);
    background: rgba(8,10,22,.85); backdrop-filter: blur(12px);
    position:sticky; top:0; z-index:100;
  }
  .brand { display:flex; align-items:center; gap:10px; font-weight:800; font-size:18px; }
  .logoN {
    width:34px; height:34px; border-radius:9px; display:grid; place-items:center;
    background: linear-gradient(135deg,var(--c1),var(--c5),var(--c6));
    color:white; font-weight:900; font-size:18px; font-family:Georgia, serif;
  }
  .nav { display:flex; gap:18px; align-items:center; font-size:13px; }
  .nav a { color:var(--mute); transition:.2s; }
  .nav a:hover { color:var(--txt); }
  .logout-btn {
    padding:8px 14px; border-radius:8px; border:1px solid var(--line);
    background:transparent; color:var(--mute); cursor:pointer; font-size:12px;
  }
  .logout-btn:hover { color:var(--red); border-color:var(--red); }

  /* STATS BAR */
  .stats-row {
    display:grid; grid-template-columns: repeat(5,1fr); gap:10px;
    padding:16px 22px; max-width:1400px; margin:0 auto;
  }
  .stat {
    background: var(--panel); border:1px solid var(--line); border-radius:14px;
    padding:14px; text-align:center;
  }
  .stat-lbl { color:var(--mute); font-size:11px; text-transform:uppercase; letter-spacing:.8px; }
  .stat-val { font-size:20px; font-weight:800; margin-top:4px; }

  /* TABS */
  .tabs {
    display:flex; gap:8px; padding:0 22px 16px; max-width:1400px; margin:0 auto;
    overflow-x:auto; scrollbar-width:none;
  }
  .tabs::-webkit-scrollbar { display:none; }
  .tab {
    padding:11px 18px; border-radius:10px; cursor:pointer;
    background: var(--panel); border:1px solid var(--line);
    font-size:13px; font-weight:600; white-space:nowrap; color:var(--mute);
    transition:.15s;
  }
  .tab:hover { color:var(--txt); }
  .tab.active {
    background: linear-gradient(90deg,var(--c1),var(--c5),var(--c6));
    color:white; border-color:transparent;
  }

  /* CONTAINER */
  .container { max-width:1400px; margin:0 auto; padding:0 22px 60px; }
  .section { display:none; }
  .section.active { display:block; }

  .section-head {
    display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;
  }
  .section-head h2 { font-size:18px; }
  .badge {
    padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700;
    background:rgba(0,230,118,.15); color:var(--green); border:1px solid rgba(0,230,118,.3);
  }
  .badge.live::before {
    content:""; display:inline-block; width:6px; height:6px; border-radius:50%;
    background:var(--green); margin-right:6px; animation:pulse 1.5s infinite;
  }
  @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }

  /* LAUNCH CARDS */
  .grid {
    display:grid; grid-template-columns: repeat(auto-fill, minmax(340px,1fr)); gap:14px;
  }
  .card {
    background: var(--panel); border:1px solid var(--line); border-radius:14px;
    padding:16px; transition:.2s;
  }
  .card:hover { border-color: var(--c5); transform: translateY(-2px); }
  .card-top {
    display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:12px;
  }
  .card-token { font-size:16px; font-weight:800; }
  .card-sym { color: var(--mute); font-size:12px; margin-top:2px; }
  .card-age {
    background: rgba(255,159,67,.15); color:var(--c2);
    padding:3px 9px; border-radius:6px; font-size:11px; font-weight:700;
  }
  .card-age.hot { background: rgba(255,107,107,.18); color:var(--c1); }
  .card-stats {
    display:grid; grid-template-columns: repeat(3,1fr); gap:8px; margin-bottom:12px;
  }
  .card-stat { background:#080a18; border-radius:8px; padding:8px; text-align:center; }
  .card-stat-lbl { color:var(--mute); font-size:10px; text-transform:uppercase; }
  .card-stat-val { font-size:13px; font-weight:700; margin-top:2px; }
  .pos { color:var(--green); }
  .neg { color:var(--red); }

  .buyers-title {
    color:var(--mute); font-size:11px; text-transform:uppercase; letter-spacing:.6px;
    margin-bottom:8px; display:flex; align-items:center; gap:6px;
  }
  .buyer-chip {
    display:inline-block; padding:5px 10px; border-radius:6px; background:#080a18;
    border:1px solid var(--line); font-size:11px; font-family: monospace;
    margin:3px 3px 0 0; cursor:pointer; transition:.15s;
  }
  .buyer-chip:hover { border-color:var(--c5); color:var(--c5); }

  .card-actions { display:flex; gap:8px; margin-top:12px; }
  .btn {
    flex:1; padding:9px; border-radius:8px; border:1px solid var(--line);
    background:#080a18; color:var(--txt); font-size:12px; font-weight:600;
    cursor:pointer; text-align:center; transition:.15s;
  }
  .btn:hover { border-color: var(--c5); color:var(--c5); }
  .btn.primary {
    background: linear-gradient(90deg,var(--c4),var(--c5));
    border-color: transparent; color:#000; font-weight:800;
  }

  /* LIVE FEED */
  .feed { display:flex; flex-direction:column; gap:8px; }
  .feed-row {
    display:flex; align-items:center; gap:12px; padding:12px 14px;
    background: var(--panel); border:1px solid var(--line); border-radius:10px;
    font-size:13px; transition:.15s;
  }
  .feed-row:hover { border-color:var(--c5); }
  .feed-time { color:var(--mute); font-size:11px; min-width:60px; font-family:monospace; }
  .feed-type {
    padding:3px 8px; border-radius:6px; font-size:11px; font-weight:800; min-width:50px; text-align:center;
  }
  .feed-type.buy { background: rgba(0,230,118,.18); color:var(--green); }
  .feed-type.sell { background: rgba(255,84,112,.18); color:var(--red); }
  .feed-wallet { font-family:monospace; font-size:12px; cursor:pointer; color:var(--c5); }
  .feed-wallet:hover { text-decoration: underline; }
  .feed-amount { font-weight:700; margin-left:auto; }
  .feed-coin { color:var(--c3); font-weight:700; }

  /* INSPECTOR */
  .inspector-input-row { display:flex; gap:8px; margin-bottom:18px; }
  .inspector-input-row input {
    flex:1; padding:12px 16px; border-radius:10px; border:1px solid var(--line);
    background:#080a18; color:var(--txt); font-size:14px; font-family:monospace; outline:none;
  }
  .inspector-input-row input:focus { border-color:var(--c5); }
  .inspector-input-row button {
    padding:12px 24px; border-radius:10px; border:none; cursor:pointer; font-weight:700;
    background: linear-gradient(90deg,var(--c1),var(--c5),var(--c6)); color:white;
  }
  .wallet-overview {
    display:grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap:12px;
    margin-bottom:20px;
  }
  .wallet-stat {
    background: var(--panel); border:1px solid var(--line); border-radius:12px;
    padding:14px; text-align:center;
  }
  .wallet-section {
    background: var(--panel); border:1px solid var(--line); border-radius:14px;
    padding:18px; margin-bottom:14px;
  }
  .wallet-section h3 {
    font-size:14px; margin-bottom:12px; color:var(--mute);
    text-transform:uppercase; letter-spacing:.6px;
  }
  .tx-row {
    display:flex; align-items:center; gap:10px; padding:10px;
    border-bottom:1px solid var(--line); font-size:12px;
  }
  .tx-row:last-child { border-bottom:none; }

  /* TOP TRADERS */
  .table-wrap { background:var(--panel); border:1px solid var(--line); border-radius:14px; overflow:hidden; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  th { background:#080a18; padding:12px 14px; text-align:left; color:var(--mute); font-size:11px;
       text-transform:uppercase; letter-spacing:.6px; font-weight:600; }
  td { padding:12px 14px; border-top:1px solid var(--line); }
  tr:hover td { background:rgba(255,255,255,.02); }
  .rank { font-weight:800; color:var(--c3); }
  .copy-btn {
    padding:5px 10px; border-radius:6px; border:1px solid var(--c5); background:transparent;
    color:var(--c5); font-size:11px; cursor:pointer; font-weight:700;
  }

  .loader { text-align:center; padding:40px; color:var(--mute); }
  .empty { text-align:center; padding:40px; color:var(--mute); }

  /* MOBILE */
  @media (max-width:800px) {
    .stats-row { grid-template-columns: repeat(2,1fr); }
    .nav { gap:10px; }
    .nav a { font-size:11px; }
    .grid { grid-template-columns: 1fr; }
    .feed-row { flex-wrap:wrap; }
  }
</style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
  <div class="login-wrap">
    <form class="login-card" method="POST">
      <div class="logoN" style="width:60px;height:60px;font-size:30px;border-radius:14px;margin:0 auto 18px;">N</div>
      <h1 class="rainbow">NexAI Trading</h1>
      <p>Whale copy-trader · A2Z wallet tracker</p>
      <?php if (!empty($error)): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <input type="password" name="password" placeholder="Enter password" autofocus required>
      <button type="submit">ENTER</button>
    </form>
  </div>

<?php else: ?>

<div class="topbar">
  <a href="/nexai.html" class="brand">
    <div class="logoN">N</div>
    <div>NexAI <span class="rainbow">Trading</span></div>
  </a>
  <div class="nav">
    <a href="/nexai.html">NexAI</a>
    <a href="/whale.html">Whale</a>
    <a href="/">CallGod</a>
    <a href="?logout=1" class="logout-btn">Logout</a>
  </div>
</div>

<div class="stats-row" id="statsRow">
  <div class="stat"><div class="stat-lbl">New Launches 24h</div><div class="stat-val" id="s-launches">—</div></div>
  <div class="stat"><div class="stat-lbl">Active Whales</div><div class="stat-val" id="s-whales">—</div></div>
  <div class="stat"><div class="stat-lbl">Whale Volume 24h</div><div class="stat-val" id="s-vol">—</div></div>
  <div class="stat"><div class="stat-lbl">Top Gainer</div><div class="stat-val" id="s-gainer">—</div></div>
  <div class="stat"><div class="stat-lbl">Last Refresh</div><div class="stat-val" id="s-refresh">—</div></div>
</div>

<div class="tabs">
  <div class="tab active" data-tab="launches">🔥 Fresh Launches</div>
  <div class="tab" data-tab="feed">🐋 Live Whale Feed</div>
  <div class="tab" data-tab="inspector">🔍 Wallet Inspector</div>
  <div class="tab" data-tab="traders">💰 Top Traders</div>
  <div class="tab" data-tab="signals">🚨 Copy Signals</div>
</div>

<div class="container">

  <!-- TAB 1: LAUNCHES -->
  <div class="section active" id="sec-launches">
    <div class="section-head">
      <h2>🔥 Fresh Solana Launches <span class="badge live">LIVE</span></h2>
      <div style="color:var(--mute);font-size:12px;">Auto-refresh 60s · Click wallet for A2Z profile</div>
    </div>
    <div id="launches-grid" class="grid">
      <div class="loader">Loading fresh launches...</div>
    </div>
  </div>

  <!-- TAB 2: LIVE FEED -->
  <div class="section" id="sec-feed">
    <div class="section-head">
      <h2>🐋 Live Whale Buy/Sell Feed <span class="badge live">LIVE</span></h2>
      <div style="color:var(--mute);font-size:12px;">Refresh 30s · Buys >$2k tracked</div>
    </div>
    <div id="feed-list" class="feed">
      <div class="loader">Loading whale activity...</div>
    </div>
  </div>

  <!-- TAB 3: INSPECTOR -->
  <div class="section" id="sec-inspector">
    <div class="section-head">
      <h2>🔍 Wallet Inspector — Full A2Z Profile</h2>
    </div>
    <div class="inspector-input-row">
      <input type="text" id="inspect-addr" placeholder="Paste Solana wallet address...">
      <button onclick="inspectWallet()">Inspect</button>
    </div>
    <div id="inspect-result">
      <div class="empty">
        Paste a wallet to see:<br><br>
        💰 SOL balance + USD value<br>
        🪙 Top token holdings<br>
        🟢 Recent buys (24h)<br>
        🔴 Recent sells (24h)<br>
        📊 Estimated PnL<br>
        🔗 Source wallet (where funds came from)
      </div>
    </div>
  </div>

  <!-- TAB 4: TOP TRADERS -->
  <div class="section" id="sec-traders">
    <div class="section-head">
      <h2>💰 Top Traders Leaderboard (24h PnL)</h2>
    </div>
    <div id="traders-table" class="table-wrap">
      <div class="loader">Loading leaderboard...</div>
    </div>
  </div>

  <!-- TAB 5: SIGNALS -->
  <div class="section" id="sec-signals">
    <div class="section-head">
      <h2>🚨 Copy Trade Signals <span class="badge live">AUTO</span></h2>
      <div style="color:var(--mute);font-size:12px;">Coins multiple whales just bought</div>
    </div>
    <div id="signals-list" class="feed">
      <div class="loader">Analyzing whale clusters...</div>
    </div>
  </div>

</div>

<script>
// ============== TAB SWITCHING ==============
document.querySelectorAll('.tab').forEach(t => {
  t.onclick = () => {
    document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
    document.querySelectorAll('.section').forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    document.getElementById('sec-' + t.dataset.tab).classList.add('active');
    if (t.dataset.tab === 'feed' && !window._feedLoaded) loadFeed();
    if (t.dataset.tab === 'traders' && !window._tradersLoaded) loadTraders();
    if (t.dataset.tab === 'signals' && !window._signalsLoaded) loadSignals();
  };
});

// ============== HELPERS ==============
const fmt = n => {
  n = Number(n) || 0;
  if (n >= 1e9) return '$' + (n/1e9).toFixed(2) + 'B';
  if (n >= 1e6) return '$' + (n/1e6).toFixed(2) + 'M';
  if (n >= 1e3) return '$' + (n/1e3).toFixed(1) + 'K';
  return '$' + n.toFixed(2);
};
const short = a => a ? a.slice(0,4) + '...' + a.slice(-4) : '';
const ago = ts => {
  const s = Math.floor((Date.now() - ts) / 1000);
  if (s < 60) return s + 's';
  if (s < 3600) return Math.floor(s/60) + 'm';
  if (s < 86400) return Math.floor(s/3600) + 'h';
  return Math.floor(s/86400) + 'd';
};

// ============== LAUNCHES ==============
async function loadLaunches() {
  try {
    const r = await fetch('trading_launches.php');
    const data = await r.json();
    const grid = document.getElementById('launches-grid');

    if (!data.coins || !data.coins.length) {
      grid.innerHTML = '<div class="empty">No fresh launches found right now</div>';
      return;
    }

    grid.innerHTML = data.coins.map(c => {
      const ageH = c.age_hours || 0;
      const hotCls = ageH < 6 ? 'hot' : '';
      const buyers = (c.top_buyers || []).slice(0, 5);
      const chg = c.price_change_24h || 0;
      const chgCls = chg >= 0 ? 'pos' : 'neg';
      return `
        <div class="card">
          <div class="card-top">
            <div>
              <div class="card-token">${c.name || c.symbol || 'Unknown'}</div>
              <div class="card-sym">$${c.symbol || '?'} · ${short(c.mint || '')}</div>
            </div>
            <div class="card-age ${hotCls}">${ageH < 1 ? '<1h' : ageH + 'h'} old</div>
          </div>
          <div class="card-stats">
            <div class="card-stat">
              <div class="card-stat-lbl">Market Cap</div>
              <div class="card-stat-val">${fmt(c.market_cap)}</div>
            </div>
            <div class="card-stat">
              <div class="card-stat-lbl">Vol 24h</div>
              <div class="card-stat-val">${fmt(c.volume_24h)}</div>
            </div>
            <div class="card-stat">
              <div class="card-stat-lbl">Change</div>
              <div class="card-stat-val ${chgCls}">${chg >= 0 ? '+' : ''}${chg.toFixed(1)}%</div>
            </div>
          </div>
          <div class="buyers-title">🐋 Top Buyer Wallets</div>
          <div>
            ${buyers.length
              ? buyers.map(b => `<span class="buyer-chip" onclick="goInspect('${b}')">${short(b)}</span>`).join('')
              : '<div style="color:var(--mute);font-size:12px;">Fetching buyers...</div>'}
          </div>
          <div class="card-actions">
            <a class="btn" href="https://dexscreener.com/solana/${c.pair_addr || c.mint}" target="_blank">DexScreener</a>
            <a class="btn primary" href="https://pump.fun/${c.mint}" target="_blank">Trade</a>
          </div>
        </div>`;
    }).join('');

    document.getElementById('s-launches').textContent = data.coins.length;
    document.getElementById('s-refresh').textContent = new Date().toLocaleTimeString().slice(0,5);
    if (data.top_gainer) document.getElementById('s-gainer').textContent = '$' + data.top_gainer;
  } catch (e) {
    document.getElementById('launches-grid').innerHTML =
      '<div class="empty">Error loading launches: ' + e.message + '</div>';
  }
}

// ============== LIVE FEED ==============
async function loadFeed() {
  window._feedLoaded = true;
  try {
    const r = await fetch('trading_whale_feed.php');
    const data = await r.json();
    const box = document.getElementById('feed-list');

    if (!data.events || !data.events.length) {
      box.innerHTML = '<div class="empty">No whale activity in last hour</div>';
      return;
    }

    box.innerHTML = data.events.map(e => {
      const type = e.type === 'sell' ? 'sell' : 'buy';
      return `
        <div class="feed-row">
          <div class="feed-time">${ago(e.timestamp * 1000)} ago</div>
          <div class="feed-type ${type}">${type.toUpperCase()}</div>
          <div class="feed-wallet" onclick="goInspect('${e.wallet}')">${short(e.wallet)}</div>
          <div>${e.action || ''} <span class="feed-coin">$${e.symbol || '?'}</span></div>
          <div class="feed-amount">${fmt(e.usd_value || 0)}</div>
        </div>`;
    }).join('');

    document.getElementById('s-whales').textContent = data.unique_wallets || '—';
    document.getElementById('s-vol').textContent = fmt(data.total_volume || 0);
  } catch (e) {
    document.getElementById('feed-list').innerHTML =
      '<div class="empty">Error: ' + e.message + '</div>';
  }
}

// ============== INSPECTOR ==============
function goInspect(addr) {
  document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
  document.querySelectorAll('.section').forEach(x => x.classList.remove('active'));
  document.querySelector('[data-tab="inspector"]').classList.add('active');
  document.getElementById('sec-inspector').classList.add('active');
  document.getElementById('inspect-addr').value = addr;
  inspectWallet();
}

async function inspectWallet() {
  const addr = document.getElementById('inspect-addr').value.trim();
  if (!addr || addr.length < 30) {
    alert('Enter valid Solana wallet address');
    return;
  }
  const box = document.getElementById('inspect-result');
  box.innerHTML = '<div class="loader">Pulling A2Z profile from Helius...</div>';

  try {
    const r = await fetch('trading_wallet.php?addr=' + encodeURIComponent(addr));
    const d = await r.json();
    if (d.error) { box.innerHTML = '<div class="empty">Error: ' + d.error + '</div>'; return; }

    const buys = d.recent_buys || [];
    const sells = d.recent_sells || [];
    const holdings = d.top_holdings || [];

    box.innerHTML = `
      <div class="wallet-overview">
        <div class="wallet-stat">
          <div class="stat-lbl">SOL Balance</div>
          <div class="stat-val">${(d.sol_balance || 0).toFixed(2)}</div>
        </div>
        <div class="wallet-stat">
          <div class="stat-lbl">USD Value</div>
          <div class="stat-val">${fmt(d.usd_value || 0)}</div>
        </div>
        <div class="wallet-stat">
          <div class="stat-lbl">Tokens Held</div>
          <div class="stat-val">${d.token_count || 0}</div>
        </div>
        <div class="wallet-stat">
          <div class="stat-lbl">24h Buys</div>
          <div class="stat-val pos">${buys.length}</div>
        </div>
        <div class="wallet-stat">
          <div class="stat-lbl">24h Sells</div>
          <div class="stat-val neg">${sells.length}</div>
        </div>
        <div class="wallet-stat">
          <div class="stat-lbl">First Seen</div>
          <div class="stat-val" style="font-size:13px;">${d.first_seen || '—'}</div>
        </div>
      </div>

      <div class="wallet-section">
        <h3>🪙 Top Token Holdings</h3>
        ${holdings.length ? holdings.map(h => `
          <div class="tx-row">
            <div style="flex:1;"><strong>$${h.symbol || '?'}</strong> <span style="color:var(--mute);">${short(h.mint)}</span></div>
            <div>${Number(h.amount || 0).toLocaleString(undefined,{maximumFractionDigits:2})}</div>
            <div style="min-width:80px;text-align:right;font-weight:700;">${fmt(h.usd_value || 0)}</div>
          </div>
        `).join('') : '<div style="color:var(--mute);">No tokens held</div>'}
      </div>

      <div class="wallet-section">
        <h3>🟢 Recent Buys (24h)</h3>
        ${buys.length ? buys.map(b => `
          <div class="tx-row">
            <div class="feed-type buy" style="min-width:48px;">BUY</div>
            <div style="flex:1;"><strong>$${b.symbol || '?'}</strong></div>
            <div style="color:var(--mute);">${ago(b.timestamp * 1000)} ago</div>
            <div style="min-width:80px;text-align:right;font-weight:700;">${fmt(b.usd_value || 0)}</div>
          </div>
        `).join('') : '<div style="color:var(--mute);">No buys in 24h</div>'}
      </div>

      <div class="wallet-section">
        <h3>🔴 Recent Sells (24h)</h3>
        ${sells.length ? sells.map(s => `
          <div class="tx-row">
            <div class="feed-type sell" style="min-width:48px;">SELL</div>
            <div style="flex:1;"><strong>$${s.symbol || '?'}</strong></div>
            <div style="color:var(--mute);">${ago(s.timestamp * 1000)} ago</div>
            <div style="min-width:80px;text-align:right;font-weight:700;">${fmt(s.usd_value || 0)}</div>
          </div>
        `).join('') : '<div style="color:var(--mute);">No sells in 24h</div>'}
      </div>

      <div class="wallet-section">
        <h3>🔗 Funding Source</h3>
        <div style="font-size:13px;line-height:1.8;">
          ${d.funding_source ? `Funded from: <span class="feed-wallet" onclick="goInspect('${d.funding_source}')">${short(d.funding_source)}</span> (${d.funding_label || 'Unknown'})` : 'Source unknown'}
        </div>
      </div>

      <div style="text-align:center;margin-top:18px;">
        <a class="btn primary" style="display:inline-block;padding:12px 30px;" href="https://solscan.io/account/${addr}" target="_blank">View on Solscan</a>
      </div>
    `;
  } catch (e) {
    box.innerHTML = '<div class="empty">Error: ' + e.message + '</div>';
  }
}

// ============== TOP TRADERS ==============
async function loadTraders() {
  window._tradersLoaded = true;
  try {
    const r = await fetch('trading_top_traders.php');
    const data = await r.json();
    const box = document.getElementById('traders-table');

    if (!data.traders || !data.traders.length) {
      box.innerHTML = '<div class="empty">Building leaderboard from whale data...</div>';
      return;
    }

    box.innerHTML = `
      <table>
        <thead><tr>
          <th>#</th><th>Wallet</th><th>24h PnL</th><th>Win Rate</th><th>Trades</th><th>Best Call</th><th></th>
        </tr></thead>
        <tbody>
          ${data.traders.map((t,i) => `
            <tr>
              <td class="rank">#${i+1}</td>
              <td><span class="feed-wallet" onclick="goInspect('${t.wallet}')">${short(t.wallet)}</span> <span style="color:var(--mute);font-size:11px;">${t.label || ''}</span></td>
              <td class="${(t.pnl_24h||0) >= 0 ? 'pos' : 'neg'}"><strong>${(t.pnl_24h||0) >= 0 ? '+' : ''}${fmt(t.pnl_24h||0)}</strong></td>
              <td>${(t.win_rate || 0).toFixed(0)}%</td>
              <td>${t.trades || 0}</td>
              <td>$${t.best_call || '—'}</td>
              <td><button class="copy-btn" onclick="goInspect('${t.wallet}')">VIEW</button></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  } catch (e) {
    document.getElementById('traders-table').innerHTML =
      '<div class="empty">Error: ' + e.message + '</div>';
  }
}

// ============== SIGNALS ==============
async function loadSignals() {
  window._signalsLoaded = true;
  try {
    const r = await fetch('trading_signals.php');
    const data = await r.json();
    const box = document.getElementById('signals-list');

    if (!data.signals || !data.signals.length) {
      box.innerHTML = '<div class="empty">No clustered whale buys detected yet</div>';
      return;
    }

    box.innerHTML = data.signals.map(s => `
      <div class="feed-row" style="padding:16px;">
        <div style="flex:1;">
          <div style="font-weight:800;font-size:14px;margin-bottom:4px;">
            🚨 ${s.whale_count} whales bought <span class="feed-coin">$${s.symbol}</span> in last ${s.window}
          </div>
          <div style="color:var(--mute);font-size:12px;">
            Total: ${fmt(s.total_usd)} · MC: ${fmt(s.market_cap)} · ${s.mint ? short(s.mint) : ''}
          </div>
          <div style="margin-top:8px;">
            ${(s.wallets || []).slice(0,5).map(w => `<span class="buyer-chip" onclick="goInspect('${w}')">${short(w)}</span>`).join('')}
          </div>
        </div>
        <div>
          <a class="btn primary" style="display:inline-block;padding:10px 18px;" href="https://pump.fun/${s.mint}" target="_blank">Trade →</a>
        </div>
      </div>
    `).join('');
  } catch (e) {
    document.getElementById('signals-list').innerHTML =
      '<div class="empty">Error: ' + e.message + '</div>';
  }
}

// ============== INIT ==============
loadLaunches();
setInterval(loadLaunches, 60000);
setInterval(() => { if (window._feedLoaded) loadFeed(); }, 30000);
setInterval(() => { if (window._signalsLoaded) loadSignals(); }, 60000);
</script>

<?php endif; ?>
</body>
</html>
