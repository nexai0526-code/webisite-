<?php
session_start();
$ADMIN_PASS = 'CallGod2026!';
if (!isset($_SESSION['admin_auth'])) {
    if (isset($_POST['password']) && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin_auth'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html><head><title>Login - Solana Alpha</title>
        <style>
        body{background:#08080F;color:#F0F0F8;font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
        .box{background:#12121E;border:1px solid rgba(153,69,255,.3);border-radius:14px;padding:40px;width:340px}
        h2{font-family:'Syne',sans-serif;margin-bottom:24px;background:linear-gradient(135deg,#9945FF,#14F195);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        input{width:100%;padding:14px;background:#0B0B13;border:1px solid rgba(255,255,255,.08);border-radius:8px;color:#fff;font-size:14px;margin-bottom:14px}
        button{width:100%;padding:14px;background:linear-gradient(135deg,#9945FF,#14F195);border:none;color:#fff;font-weight:600;border-radius:8px;cursor:pointer}
        </style></head><body>
        <form class="box" method="POST">
            <h2>🟣 Solana Alpha</h2>
            <input type="password" name="password" placeholder="Admin Password" autofocus>
            <button>Enter</button>
        </form>
<div id="holdingsModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:1000;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)this.style.display='none'">
  <div style="background:var(--card);border:1px solid rgba(153,69,255,.3);border-radius:16px;max-width:500px;width:100%;max-height:80vh;overflow-y:auto;padding:24px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
      <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700" id="modalTitle">Whale Holdings</div>
      <button onclick="document.getElementById('holdingsModal').style.display='none'" style="background:rgba(255,71,87,.1);border:1px solid rgba(255,71,87,.2);color:var(--red);border-radius:8px;padding:6px 12px;cursor:pointer">✕</button>
    </div>
    <div id="modalBody"><div class="empty">Loading...</div></div>
  </div>
</div>

</body></html>
        <?php
        exit;
    }
}
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_destroy();
    header('Location: /solana.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Solana Alpha - NexAI</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600;700;800&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
--sol-purple:#9945FF;--sol-green:#14F195;
--bg:#08080F;--card:#12121E;--text:#F0F0F8;--text2:#9B9BB0;--text3:#5B5B72;
--border:rgba(255,255,255,.05);--green:#00E676;--red:#FF4757;--amber:#FFD32A;--cyan:#00D2FF;
--solgrad:linear-gradient(135deg,#9945FF,#14F195);
}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;padding:16px;min-height:100vh;line-height:1.5}
.container{max-width:1400px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;background:var(--card);border:1px solid var(--border);border-radius:14px;margin-bottom:18px;flex-wrap:wrap;gap:14px}
.header h1{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;display:flex;align-items:center;gap:12px}
.logo-icon{width:36px;height:36px;border-radius:10px;background:var(--solgrad);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff}
.live-badge{font-size:9px;padding:4px 8px;background:rgba(153,69,255,.15);color:var(--sol-purple);border-radius:4px;font-family:monospace;letter-spacing:1px;animation:pulse 2s infinite}
.header-links{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.header-links a,.header-links button{padding:8px 14px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text2);font-size:11px;text-decoration:none;font-family:'JetBrains Mono',monospace;letter-spacing:1px;cursor:pointer;transition:all .2s}
.header-links a:hover{color:var(--sol-purple);border-color:rgba(153,69,255,.3)}
.logout{background:rgba(255,71,87,.08)!important;color:var(--red)!important;border-color:rgba(255,71,87,.2)!important}

.tabs{display:flex;gap:6px;background:var(--card);border:1px solid var(--border);border-radius:14px;padding:8px;margin-bottom:18px;overflow-x:auto}
.tab{flex:1;min-width:140px;padding:12px 18px;background:transparent;border:1px solid transparent;border-radius:10px;color:var(--text2);font-family:'Syne',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap;text-align:center}
.tab:hover{color:var(--text)}
.tab.active{background:rgba(153,69,255,.12);border-color:rgba(153,69,255,.4);color:var(--sol-purple)}
.tab-content{display:none}
.tab-content.active{display:block}

.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.s-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:16px 18px;position:relative;overflow:hidden}
.s-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.s-card.c1::after{background:var(--sol-purple)}
.s-card.c2::after{background:var(--sol-green)}
.s-card.c3::after{background:var(--amber)}
.s-card.c4::after{background:var(--cyan)}
.s-lab{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text3);letter-spacing:1.5px;margin-bottom:6px}
.s-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:700}
.s-val.purple{color:var(--sol-purple)}.s-val.green{color:var(--sol-green)}.s-val.amber{color:var(--amber)}.s-val.cyan{color:var(--cyan)}
.s-sub{font-size:10px;color:var(--text3);margin-top:3px;font-family:monospace}

.panel{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:18px}
.p-head{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.p-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:600;display:flex;align-items:center;gap:8px}
.p-title .dot{width:8px;height:8px;border-radius:50%;background:var(--sol-purple);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.refresh-btn{background:rgba(153,69,255,.12);border:1px solid rgba(153,69,255,.3);color:var(--sol-purple);padding:6px 12px;border-radius:6px;font-size:10px;cursor:pointer;font-family:'JetBrains Mono',monospace;letter-spacing:1px}

.filters{padding:12px 18px;background:rgba(0,0,0,.2);border-bottom:1px solid var(--border);display:flex;gap:6px;flex-wrap:wrap}
.fbtn{padding:6px 12px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;color:var(--text2);font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:1px;cursor:pointer;transition:all .2s}
.fbtn:hover{border-color:var(--sol-purple);color:var(--sol-purple)}
.fbtn.active{background:rgba(20,241,149,.15);border-color:var(--sol-green);color:var(--sol-green)}

.sig-card{border-bottom:1px solid rgba(255,255,255,.03);transition:background .2s}
.sig-card:hover{background:rgba(153,69,255,.03)}
.sig-head{padding:14px 22px;display:grid;grid-template-columns:auto 1fr auto auto;gap:14px;align-items:center;cursor:pointer}
.sig-icon{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(153,69,255,.2),rgba(20,241,149,.1));display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--sol-purple)}
.sig-info{min-width:0}
.sig-name{font-family:'Syne',sans-serif;font-size:16px;font-weight:700}
.sig-meta{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text3);margin-top:3px}
.sig-grade{font-family:'Syne',sans-serif;font-weight:800;padding:8px 14px;border-radius:10px;font-size:14px;text-align:center;min-width:70px}
.sig-toggle{font-size:18px;color:var(--text3);transition:transform .2s;padding:4px}
.sig-card.open .sig-toggle{transform:rotate(180deg)}
.sig-body{display:none;padding:0 22px 18px;background:rgba(0,0,0,.15)}
.sig-card.open .sig-body{display:block}

.whales-title{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text3);letter-spacing:2px;margin-bottom:10px;margin-top:14px}
.whale-chips{display:flex;flex-wrap:wrap;gap:6px;margin:10px 0}
.whale-chip{font-size:10px;padding:4px 8px;background:rgba(153,69,255,.1);color:var(--sol-purple);border-radius:6px;font-family:monospace}

.actions{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}
.action{padding:8px 14px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text2);font-family:'JetBrains Mono',monospace;font-size:10px;text-decoration:none;letter-spacing:1px;transition:all .2s}
.action:hover{border-color:var(--sol-purple);color:var(--sol-purple)}
.action.ape{background:var(--solgrad);border:none;color:#fff;font-weight:700}

.whale-row{display:grid;grid-template-columns:auto 1fr auto;gap:12px;align-items:center;padding:12px 22px;border-bottom:1px solid rgba(255,255,255,.03);transition:background .2s}
.whale-row:hover{background:rgba(153,69,255,.02)}
.wr-icon{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,rgba(153,69,255,.15),rgba(20,241,149,.05));display:flex;align-items:center;justify-content:center;font-size:16px}
.wr-info{min-width:0}
.wr-label{font-family:'Syne',sans-serif;font-size:14px;font-weight:600}
.wr-meta{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);margin-top:2px;word-break:break-all}
.wr-score{font-family:'Syne',sans-serif;font-weight:800;font-size:13px;color:var(--sol-green);text-align:right}

.empty{padding:40px 22px;text-align:center;color:var(--text3);font-size:13px}

@media(max-width:768px){
  body{padding:10px}
  .header h1{font-size:16px}
  .tabs{padding:6px}
  .tab{padding:10px 12px;font-size:11px;min-width:0}
  .stats{grid-template-columns:1fr 1fr;gap:8px}
  .s-card{padding:12px}
  .s-val{font-size:18px}
  .sig-head{grid-template-columns:auto 1fr auto;padding:12px 14px;gap:10px}
  .sig-icon{width:38px;height:38px;font-size:16px}
  .sig-name{font-size:14px}
  .sig-body{padding:0 14px 14px}
  .whale-row{padding:10px 14px}
}
</style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1><div class="logo-icon">◎</div>Solana Alpha<span class="live-badge">● LIVE</span></h1>
    <div class="header-links">
      <a href="/trading.php">Ξ ETH Panel</a>
      <a href="/">CallGod</a>
      <form method="POST" style="display:inline"><input type="hidden" name="action" value="logout"><button class="logout">Logout</button></form>
    </div>
  </div>

  <div class="tabs">
    <button class="tab active" data-tab="top">⭐ Top Whales</button>
    <button class="tab" data-tab="cex">🚀 CEX Radar</button>
    <button class="tab" data-tab="live">🔴 Live Buys</button>
    <button class="tab" data-tab="hot">🔥 Hot Coins</button>
    <button class="tab" data-tab="signals">🎯 Alpha Signals</button>
    <button class="tab" data-tab="whales">🐋 Whale Discovery</button>
  </div>

  <!-- TAB: LIVE BUYS -->
  <div class="tab-content active" id="tab-top">
    <div class="stats">
      <div class="s-card c1"><div class="s-lab">RANKED</div><div class="s-val purple" id="twCount">10</div><div class="s-sub">Best holders</div></div>
      <div class="s-card c2"><div class="s-lab">SCANNED</div><div class="s-val green" id="twScanned">40</div><div class="s-sub">Top alpha</div></div>
      <div class="s-card c3"><div class="s-lab">CRITERIA</div><div class="s-val amber" style="font-size:15px">💎 HOLD</div><div class="s-sub">+alpha+value</div></div>
      <div class="s-card c4"><div class="s-lab">UPDATED</div><div class="s-val cyan" id="twUpdated" style="font-size:13px">—</div><div class="s-sub">Last scan</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div><span>⭐ Best 10 — Diamond-Hand Whales (buy good coins &amp; HOLD)</span></div>
        <button class="refresh-btn" onclick="loadTopWhales()">🔄 RELOAD</button>
      </div>
      <div id="topList"><div class="empty">Loading best whales...</div></div>
    </div>
  </div>
  <div class="tab-content" id="tab-cex">
    <div class="stats">
      <div class="s-card c1"><div class="s-lab">CANDIDATES</div><div class="s-val" style="color:#FF4757" id="cexCand">—</div><div class="s-sub">Tier-1 next?</div></div>
      <div class="s-card c2"><div class="s-lab">SCANNED</div><div class="s-val purple" id="cexScanned">—</div><div class="s-sub">Coins</div></div>
      <div class="s-card c3"><div class="s-lab">ON CEX</div><div class="s-val green" id="cexListed">—</div><div class="s-sub">Have CEX</div></div>
      <div class="s-card c4"><div class="s-lab">UPDATED</div><div class="s-val cyan" id="cexUpdated" style="font-size:13px">—</div><div class="s-sub">Last scan</div></div>
    </div>
    <div class="panel" style="background:rgba(255,71,87,.04);border-color:rgba(255,71,87,.15)">
      <div style="padding:12px 14px;font-size:11px;color:var(--text2);line-height:1.5">
        🪜 <b>How it works:</b> coins climb pump.fun → DEX → Tier-2 CEX (Gate/MEXC/Bybit) → Tier-1 (Binance/Coinbase/Upbit). A coin already on Tier-2 but NOT Tier-1 = 🔥 <b style="color:#FF4757">listing candidate</b>. <span style="color:var(--text3)">Signal only, not a guarantee — DYOR.</span>
      </div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div><span>🚀 CEX Listing Radar — whale coins by listing readiness</span></div>
        <button class="refresh-btn" onclick="loadCexRadar()">🔄 RELOAD</button>
      </div>
      <div id="cexList"><div class="empty">Loading radar...</div></div>
    </div>
  </div>
  <div class="tab-content" id="tab-live">
    <div class="stats">
      <div class="s-card c2"><div class="s-lab">🔴 LIVE BUYS</div><div class="s-val green" id="liveCount">—</div><div class="s-sub">Recent</div></div>
      <div class="s-card c1"><div class="s-lab">WHALES</div><div class="s-val purple" id="liveWhales">300</div><div class="s-sub">Tracked</div></div>
      <div class="s-card c3"><div class="s-lab">AUTO-SCAN</div><div class="s-val amber" id="liveTimer">5s</div><div class="s-sub">Next scan</div></div>
      <div class="s-card c4"><div class="s-lab">STATUS</div><div class="s-val cyan" id="liveStatus">●</div><div class="s-sub">Scanning</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div><span>🔴 Live Whale Buys — Auto-Refresh</span></div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <label style="font-size:11px;color:var(--text2);display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="checkbox" id="autoToggle" checked style="cursor:pointer"> Auto
          </label>
          <button class="refresh-btn" onclick="loadLive(true)">🔄 SCAN NOW</button>
        </div>
      </div>
      <div id="liveList"><div class="empty">🔍 Scanning whale buys...</div></div>
    </div>
  </div>

  <!-- TAB: HOT COINS -->
  <div class="tab-content" id="tab-hot">
    <div class="stats">
      <div class="s-card c2"><div class="s-lab">🔥 HOT COINS</div><div class="s-val green" id="hotCount">—</div><div class="s-sub">3+ whales buying</div></div>
      <div class="s-card c1"><div class="s-lab">WHALES</div><div class="s-val purple" id="hotWhales">300</div><div class="s-sub">Tracked</div></div>
      <div class="s-card c3"><div class="s-lab">ACTIVE</div><div class="s-val amber" id="hotActive">—</div><div class="s-sub">24h</div></div>
      <div class="s-card c4"><div class="s-lab">COINS</div><div class="s-val cyan" id="hotCoins">—</div><div class="s-sub">Scanned</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div><span>🔥 HOT COINS — 3+ Whales Buying Same Coin</span></div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <span id="hotMeta" style="font-size:10px;color:var(--text3);font-family:monospace"></span>
          <button class="refresh-btn" onclick="loadSignals(true)">🔄 SCAN NEXT</button>
        </div>
      </div>
      <div id="hotList"><div class="empty">🔍 Scanning for hot coins...</div></div>
    </div>
  </div>

  <!-- TAB: ALPHA SIGNALS -->
  <div class="tab-content" id="tab-signals">
    <div class="stats">
      <div class="s-card c1"><div class="s-lab">SOLANA WHALES</div><div class="s-val purple" id="sigWhales">300</div><div class="s-sub">Tracked live</div></div>
      <div class="s-card c2"><div class="s-lab">🟢 STRONG BUYS</div><div class="s-val green" id="sigStrong">—</div><div class="s-sub">Score 20+</div></div>
      <div class="s-card c3"><div class="s-lab">ACTIVE WHALES</div><div class="s-val amber" id="sigActive">—</div><div class="s-sub">24h</div></div>
      <div class="s-card c4"><div class="s-lab">COINS TRACKED</div><div class="s-val cyan" id="sigCoins">—</div><div class="s-sub">Last scan</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div><span>🎯 Alpha Signals — Whales Buying Now</span></div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <span id="sigMeta" style="font-size:10px;color:var(--text3);font-family:monospace"></span>
          <button class="refresh-btn" onclick="loadSignals(true)">🔄 SCAN NEXT</button>
        </div>
      </div>
      <div class="filters">
        <button class="fbtn active" data-filter="all">ALL</button>
        <button class="fbtn" data-filter="STRONG BUY">🟢 STRONG BUY</button>
        <button class="fbtn" data-filter="BUY">🔵 BUY</button>
      </div>
      <div id="signalsList"><div class="empty">Loading Solana alpha signals...</div></div>
    </div>
  </div>

  <!-- TAB: WHALE DISCOVERY -->
  <div class="tab-content" id="tab-whales">
    <div class="stats">
      <div class="s-card c1"><div class="s-lab">DISCOVERED</div><div class="s-val purple" id="discTotal">300</div><div class="s-sub">Auto-found</div></div>
      <div class="s-card c2"><div class="s-lab">TOP SCORE</div><div class="s-val green" id="discTopScore">100</div><div class="s-sub">Max</div></div>
      <div class="s-card c3"><div class="s-lab">SHOWING</div><div class="s-val amber" id="discShowing">—</div><div class="s-sub">Top whales</div></div>
      <div class="s-card c4"><div class="s-lab">COINS CAUGHT</div><div class="s-val cyan" id="discCoins">—</div><div class="s-sub">Total</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div><span>🐋 Discovered Whales — Ranked by Score</span></div>
        <button class="refresh-btn" onclick="loadDiscovery(true)">🔄 REFRESH</button>
      </div>
      <div id="discoveryList"><div class="empty">Click to load whale discovery...</div></div>
    </div>
  </div>
</div>

<script>


async function loadCexRadar(){
  const el=document.getElementById('cexList');
  el.innerHTML='<div class="empty">Loading radar...</div>';
  try{
    const r=await fetch('/cex_radar.php?t='+Date.now());
    const d=await r.json();
    if(!d.success||!d.coins||!d.coins.length){el.innerHTML='<div class="empty">Not scanned yet. Run scan_cex.php on server.</div>';return}
    document.getElementById('cexScanned').textContent=d.scanned;
    document.getElementById('cexCand').textContent=d.coins.filter(c=>c.tier2&&!c.tier1).length;
    document.getElementById('cexListed').textContent=d.coins.filter(c=>c.on_cg&&c.cex_count>0).length;
    document.getElementById('cexUpdated').textContent=(d.updated_at||'').split(' ')[1]||'—';
    let h='';
    d.coins.forEach(c=>{
      const mc=c.market_cap>0?('$'+(c.market_cap>=1e6?(c.market_cap/1e6).toFixed(1)+'M':(c.market_cap/1e3).toFixed(0)+'k')):'—';
      const vol=c.volume_24h>0?('$'+(c.volume_24h>=1e6?(c.volume_24h/1e6).toFixed(1)+'M':(c.volume_24h/1e3).toFixed(0)+'k')):'—';
      h+='<div class="sig-card" style="border-left:3px solid '+c.color+'">';
      h+='<div class="sig-head" style="cursor:default">';
      h+='<div class="sig-icon" style="background:'+c.color+'20;color:'+c.color+';font-size:16px">'+(c.tier2&&!c.tier1?'🔥':c.on_cg?(c.tier1?'✅':'📈'):'⏳')+'</div>';
      h+='<div class="sig-info">';
      h+='<div class="sig-name">$'+c.coin+' <span style="font-size:9px;color:'+c.color+';font-weight:700">'+c.status+'</span></div>';
      h+='<div class="sig-meta">'+c.detail+'</div>';
      h+='<div class="sig-meta">🐋 '+c.buy_count+' whale buys · 💰 mcap '+mc+' · 📊 vol '+vol+(c.cex_count?' · 🏦 '+c.cex_count+' CEX':'')+'</div>';
      if(c.cexes&&c.cexes.length) h+='<div class="sig-meta" style="color:var(--text)">🏦 '+c.cexes.join(', ')+'</div>';
      h+='</div>';
      h+='<a href="'+c.dex_url+'" target="_blank" style="text-decoration:none;font-size:18px;padding:4px">📊</a>';
      h+='</div></div>';
    });
    el.innerHTML=h;
  }catch(e){el.innerHTML='<div class="empty">Error: '+e.message+'</div>';}
}

async function loadTopWhales(){
  const el=document.getElementById('topList');
  el.innerHTML='<div class="empty">Loading best whales...</div>';
  try{
    const r=await fetch('/top_whales.php?t='+Date.now());
    const d=await r.json();
    if(!d.success||!d.top||!d.top.length){el.innerHTML='<div class="empty">Not scanned yet. Run rank_whales.php on server.</div>';return}
    document.getElementById('twCount').textContent=d.top.length;
    document.getElementById('twScanned').textContent=d.scanned||'—';
    document.getElementById('twUpdated').textContent=(d.updated_at||'').split(' ')[1]||d.updated_at||'—';
    let h='';
    d.top.forEach(w=>{
      const medal=w.rank===1?'🥇':w.rank===2?'🥈':w.rank===3?'🥉':('#'+w.rank);
      const isHolder=w.meme_holds>0;
      const tag=isHolder
        ?'<span style="font-size:9px;background:rgba(20,241,149,.15);color:var(--sol-green);padding:2px 8px;border-radius:6px">💎 HOLDER</span>'
        :'<span style="font-size:9px;background:rgba(255,159,67,.15);color:#FF9F43;padding:2px 8px;border-radius:6px">⚡ FLIPPER</span>';
      h+='<div class="sig-card">';
      h+='<div class="sig-head" style="cursor:default">';
      h+='<div class="sig-icon" style="background:linear-gradient(135deg,rgba(153,69,255,.25),rgba(20,241,149,.1));font-size:18px">'+medal+'</div>';
      h+='<div class="sig-info">';
      h+='<div class="sig-name">'+w.label+' '+tag+'</div>';
      h+='<div class="sig-meta" style="font-family:monospace;font-size:10px">'+w.address.slice(0,8)+'...'+w.address.slice(-6)+'</div>';
      h+='<div class="sig-meta" style="margin-top:4px">🎯 '+w.alpha+' coins caught · 💎 '+w.meme_holds+' holding · 💰 $'+Number(w.total_value).toLocaleString()+'</div>';
      if(isHolder && w.meme_value>0) h+='<div class="sig-meta" style="color:var(--sol-green)">📈 Meme bags: $'+Number(w.meme_value).toLocaleString()+(w.top_hold&&w.top_hold[0]!=' '?' · top: '+w.top_hold:'')+'</div>';
      h+='</div>';
      h+='<div class="sig-grade" style="background:rgba(153,69,255,.15);color:var(--sol-purple)">'+w.score+'<br><span style="font-size:8px">SCORE</span></div>';
      h+='</div>';
      h+='<div style="padding:8px 14px;border-top:1px solid rgba(255,255,255,.05);display:flex;gap:8px;flex-wrap:wrap">';
      h+='<button onclick="loadHoldings(\''+w.address+'\',\''+w.label+'\')" style="font-size:11px;padding:5px 14px;background:rgba(153,69,255,.12);border:1px solid rgba(153,69,255,.3);color:var(--sol-purple);border-radius:7px;cursor:pointer">👁️ Full Holdings</button>';
      h+='<a href="https://solscan.io/account/'+w.address+'" target="_blank" style="font-size:11px;padding:5px 14px;background:rgba(0,206,201,.12);border:1px solid rgba(0,206,201,.3);color:#00D2FF;border-radius:7px;text-decoration:none">🔗 Solscan</a>';
      h+='</div>';
      h+='</div>';
    });
    el.innerHTML=h;
  }catch(e){el.innerHTML='<div class="empty">Error loading: '+e.message+'</div>';}
}

let allSignals=[];
let currentFilter='all';
window.solBatch=0;

let liveSeen={};
let liveBatch=0;
let liveCountdown=5;
let liveInterval=null;

async function loadLive(manual){
  try{
    if(manual) liveBatch=(liveBatch+1)%12;
    document.getElementById('liveStatus').textContent='●';
    document.getElementById('liveStatus').style.color='var(--amber)';
    const r=await fetch('/whale_signals.php?batch='+liveBatch+'&t='+Date.now());
    const d=await r.json();
    if(!d.success) return;
    const s=d.stats;
    document.getElementById('liveWhales').textContent=s.whales_tracked||300;
    document.getElementById('liveStatus').textContent='●';
    document.getElementById('liveStatus').style.color='var(--sol-green)';
    // Build buy events from signals (each signal = whales buying that coin)
    let buys=[];
    (d.alpha_signals||[]).forEach(sig=>{
      const co=(sig.coin||'').replace(/\$/g,'').toUpperCase();
      if(['SOL','USDC','USDT','WSOL','USD1'].includes(co)) return;
      if(sig.buy_count>0){
        const labels=sig.buy_whales||[];
        const addrs=sig.buy_whale_addrs||[];
        const pairs=[];const seen={};
        labels.forEach((lb,i)=>{const a=addrs[i]||'';if(a&&!seen[a]){seen[a]=1;pairs.push({label:lb,addr:a});}});
        buys.push({
          coin:(sig.coin||'').replace(/\$+/g,'$'),
          mint:sig.mint,
          buyers:[...new Set(labels)],
          buyerPairs:pairs,
          buy_count:sig.buy_count,
          ago:sig.last_buy_ago||'?',
          score:sig.score,
          signal:sig.signal,
          color:sig.signal_color,
          hot:sig.is_hot,
          key:sig.mint+'_'+sig.buy_count
        });
      }
    });
    if(buys.length===0){if(!document.getElementById('liveList').querySelector('.sig-card')) document.getElementById('liveList').innerHTML='<div class="empty">No buys this batch. Scanning next...</div>';return}
    // Mark new ones
    buys.forEach(b=>{ b.isNew = !liveSeen[b.key]; liveSeen[b.key]=true; });
    document.getElementById('liveCount').textContent=buys.length;
    renderLive(buys);
  }catch(e){console.error(e);document.getElementById('liveStatus').textContent='●';document.getElementById('liveStatus').style.color='var(--red)';}
}

function renderLive(buys){
  let h='';
  buys.forEach((b,idx)=>{
    const newBadge=b.isNew?'<span style="font-size:9px;background:var(--red);color:#fff;padding:2px 8px;border-radius:6px;margin-left:8px;animation:pulse 1s infinite">🆕 NEW</span>':'';
    const hotBadge=b.hot?'<span style="font-size:9px;background:rgba(255,159,67,.2);color:#FF9F43;padding:2px 8px;border-radius:6px;margin-left:6px">🔥 HOT</span>':'';
    const flash=b.isNew?'style="background:rgba(0,230,118,.06);border-left:3px solid var(--sol-green)"':'';
    const eid='lb'+idx;
    h+='<div class="sig-card" '+flash+'>';
    h+='<div class="sig-head" style="cursor:pointer" onclick="var e=document.getElementById(\''+eid+'\');e.style.display=e.style.display===\'block\'?\'none\':\'block\'">';
    h+='<div class="sig-icon" style="background:linear-gradient(135deg,rgba(0,230,118,.2),rgba(20,241,149,.1));color:var(--sol-green)">🟢</div>';
    h+='<div class="sig-info"><div class="sig-name">'+b.coin+newBadge+hotBadge+'</div>';
    h+='<div class="sig-meta">🐋 '+b.buyers.length+' whale(s) buying · '+b.buy_count+' buys · '+b.ago+' ago · tap ▾</div></div>';
    h+='<div class="sig-grade" style="background:'+b.color+'20;color:'+b.color+'">'+b.score+'<br><span style="font-size:8px">'+b.signal+'</span></div>';
    h+='<a href="https://dexscreener.com/solana/'+b.mint+'" target="_blank" onclick="event.stopPropagation()" style="text-decoration:none;font-size:18px;padding:4px">📊</a>';
    h+='</div>';
    // expandable whale list
    h+='<div id="'+eid+'" style="display:none;padding:10px 14px;border-top:1px solid rgba(255,255,255,.05)">';
    if(b.buyerPairs && b.buyerPairs.length){
      b.buyerPairs.forEach((p,wi)=>{
        const bid=eid+'_'+wi;
        h+='<div style="display:flex;align-items:center;gap:8px;padding:6px 0;flex-wrap:wrap">';
        h+='<span style="font-size:12px;font-weight:600;min-width:90px">🐋 '+p.label+'</span>';
        h+='<span style="font-size:9px;color:var(--text3);font-family:monospace">'+p.addr.slice(0,6)+'...'+p.addr.slice(-4)+'</span>';
        h+='<button onclick="event.stopPropagation();loadHoldings(\''+p.addr+'\',\''+p.label+'\')" style="font-size:10px;padding:3px 10px;background:rgba(153,69,255,.12);border:1px solid rgba(153,69,255,.3);color:var(--sol-purple);border-radius:6px;cursor:pointer">👁️ Wallet</button>';
        h+='<button onclick="event.stopPropagation();checkHold(\''+p.addr+'\',\''+b.mint+'\',\''+bid+'\')" style="font-size:10px;padding:3px 10px;background:rgba(0,206,201,.12);border:1px solid rgba(0,206,201,.3);color:#00D2FF;border-radius:6px;cursor:pointer">🔍 Hold?</button>';
        h+='<span id="'+bid+'" style="font-size:10px;font-weight:700"></span>';
        h+='</div>';
      });
    } else {
      h+='<div style="font-size:11px;color:var(--text3)">No whale addresses for this signal.</div>';
    }
    h+='</div>';
    h+='</div>';
  });
  document.getElementById('liveList').innerHTML=h;
}

async function checkHold(wallet, mint, badgeId){
  const el=document.getElementById(badgeId);
  if(!el) return;
  el.textContent='⏳';el.style.color='var(--text3)';
  try{
    const r=await fetch('/whale_holds.php?wallet='+wallet+'&mint='+mint);
    const d=await r.json();
    if(!d.success){el.textContent='?';return}
    if(d.holds){
      el.textContent='✅ HOLDING ($'+d.value_usd.toLocaleString()+')';
      el.style.color='var(--sol-green)';
    } else {
      el.textContent='🔴 SOLD';
      el.style.color='var(--red)';
    }
  }catch(e){el.textContent='err';}
}

function startLiveTimer(){
  if(liveInterval) clearInterval(liveInterval);
  liveCountdown=5;
  liveInterval=setInterval(()=>{
    if(!document.getElementById('autoToggle').checked){document.getElementById('liveTimer').textContent='off';return}
    if(!document.getElementById('tab-live').classList.contains('active')) return;
    liveCountdown--;
    document.getElementById('liveTimer').textContent=liveCountdown+'s';
    if(liveCountdown<=0){
      liveBatch=(liveBatch+1)%12;
      loadLive(false);
      liveCountdown=5;
    }
  },1000);
}

async function loadSignals(force){
  try{
    document.getElementById('signalsList').innerHTML='<div class="empty">🔍 Scanning 25 whales... (~15s)</div>';
    const url='/whale_signals.php?batch='+window.solBatch+(force?'&t='+Date.now():'');
    window.solBatch=(window.solBatch+1)%12;
    const r=await fetch(url);
    const d=await r.json();
    if(!d.success) return;
    const s=d.stats;
    document.getElementById('sigWhales').textContent=s.whales_tracked||300;
    document.getElementById('sigStrong').textContent=s.strong_buys||0;
    document.getElementById('sigActive').textContent=s.whales_with_activity||0;
    document.getElementById('sigCoins').textContent=s.unique_coins||0;
    document.getElementById('sigMeta').textContent='Batch '+(s.batch!==undefined?s.batch:'?')+'/'+(s.total_batches||12)+' · '+d.updated_at;
    allSignals=(d.alpha_signals||[]).filter(x=>{
      const co=(x.coin||'').replace(/\$/g,'').toUpperCase();
      return !['SOL','USDC','USDT','WSOL','USD1'].includes(co);
    });
    document.getElementById('hotCount').textContent=allSignals.filter(s=>s.is_hot).length;
    document.getElementById('hotWhales').textContent=s.whales_tracked||300;
    document.getElementById('hotActive').textContent=s.whales_with_activity||0;
    document.getElementById('hotCoins').textContent=s.unique_coins||0;
    document.getElementById('hotMeta').textContent='Batch '+(s.batch!==undefined?s.batch:'?')+'/'+(s.total_batches||12)+' · '+d.updated_at;
    renderHot();
    renderSignals();
  }catch(e){console.error(e);document.getElementById('signalsList').innerHTML='<div class="empty">Error loading.</div>'}
}

function fmtNum(n){if(n===null||n===undefined)return '—';n=parseFloat(n);if(n>=1e6)return '$'+(n/1e6).toFixed(1)+'M';if(n>=1e3)return '$'+(n/1e3).toFixed(0)+'K';return '$'+n.toFixed(2);}
function fmtPrice(p){if(!p)return '—';p=parseFloat(p);if(p<0.0001)return '$'+p.toExponential(2);if(p<1)return '$'+p.toFixed(6);return '$'+p.toFixed(4);}
function renderHot(){
  let hot=allSignals.filter(s=>s.is_hot);
  if(!hot||hot.length===0){document.getElementById('hotList').innerHTML='<div class="empty">No hot coins this batch (need 3+ whales). Click SCAN NEXT.</div>';return}
  let h='';
  hot.forEach((sig,idx)=>{
    const coin=(sig.coin||'').replace(/\$+/g,'$');
    const buyW=[...new Set(sig.buy_whales||[])];
    const ch1h=sig.ch1h!==null&&sig.ch1h!==undefined?parseFloat(sig.ch1h):null;
    const ch24h=sig.ch24h!==null&&sig.ch24h!==undefined?parseFloat(sig.ch24h):null;
    const ch1hColor=ch1h===null?'var(--text3)':(ch1h>=0?'var(--green)':'var(--red)');
    const ch24hColor=ch24h===null?'var(--text3)':(ch24h>=0?'var(--green)':'var(--red)');
    const ch1hTxt=ch1h===null?'—':(ch1h>=0?'+':'')+ch1h.toFixed(1)+'%';
    const ch24hTxt=ch24h===null?'—':(ch24h>=0?'+':'')+ch24h.toFixed(1)+'%';
    h+='<div class="sig-card open" id="hot-'+idx+'">';
    h+='<div class="sig-head" onclick="document.getElementById(\'hot-'+idx+'\').classList.toggle(\'open\')">';
    h+='<div class="sig-icon" style="background:linear-gradient(135deg,rgba(255,71,87,.2),rgba(255,159,67,.1));color:#FF9F43">🔥</div>';
    h+='<div class="sig-info"><div class="sig-name">'+coin+' <span style="font-size:10px;background:rgba(255,71,87,.15);color:#FF4757;padding:2px 8px;border-radius:6px;margin-left:6px">HOT · '+buyW.length+' whales</span></div>';
    h+='<div class="sig-meta">⏰ First buy: '+(sig.first_buy_ago||'?')+' ago · Latest: '+(sig.last_buy_ago||'?')+' ago</div></div>';
    h+='<div class="sig-grade" style="background:'+sig.signal_color+'20;color:'+sig.signal_color+'">'+sig.score+'<br><span style="font-size:8px">'+sig.signal+'</span></div>';
    h+='<div class="sig-toggle">▼</div></div>';
    h+='<div class="sig-body">';
    // Price details grid
    h+='<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:14px 0">';
    h+='<div style="background:rgba(0,0,0,.2);padding:10px;border-radius:8px"><div style="font-size:9px;color:var(--text3);font-family:monospace;letter-spacing:1px">PRICE</div><div style="font-size:15px;font-weight:700;margin-top:4px">'+fmtPrice(sig.price)+'</div></div>';
    h+='<div style="background:rgba(0,0,0,.2);padding:10px;border-radius:8px"><div style="font-size:9px;color:var(--text3);font-family:monospace;letter-spacing:1px">1H</div><div style="font-size:15px;font-weight:700;margin-top:4px;color:'+ch1hColor+'">'+ch1hTxt+'</div></div>';
    h+='<div style="background:rgba(0,0,0,.2);padding:10px;border-radius:8px"><div style="font-size:9px;color:var(--text3);font-family:monospace;letter-spacing:1px">24H</div><div style="font-size:15px;font-weight:700;margin-top:4px;color:'+ch24hColor+'">'+ch24hTxt+'</div></div>';
    h+='<div style="background:rgba(0,0,0,.2);padding:10px;border-radius:8px"><div style="font-size:9px;color:var(--text3);font-family:monospace;letter-spacing:1px">MCAP</div><div style="font-size:14px;font-weight:700;margin-top:4px">'+fmtNum(sig.mc)+'</div></div>';
    h+='<div style="background:rgba(0,0,0,.2);padding:10px;border-radius:8px"><div style="font-size:9px;color:var(--text3);font-family:monospace;letter-spacing:1px">LIQUIDITY</div><div style="font-size:14px;font-weight:700;margin-top:4px">'+fmtNum(sig.liq)+'</div></div>';
    h+='<div style="background:rgba(0,0,0,.2);padding:10px;border-radius:8px"><div style="font-size:9px;color:var(--text3);font-family:monospace;letter-spacing:1px">VOL 24H</div><div style="font-size:14px;font-weight:700;margin-top:4px">'+fmtNum(sig.vol24)+'</div></div>';
    h+='</div>';
    // Buy/sell pressure
    h+='<div style="display:flex;gap:14px;margin:12px 0;padding:10px;background:rgba(0,0,0,.15);border-radius:8px">';
    h+='<div style="flex:1;text-align:center"><div style="font-size:9px;color:var(--text3);font-family:monospace">BUY PRESSURE</div><div style="font-size:18px;font-weight:800;color:var(--green);margin-top:4px">'+sig.buy_count+'</div></div>';
    h+='<div style="width:1px;background:var(--border)"></div>';
    h+='<div style="flex:1;text-align:center"><div style="font-size:9px;color:var(--text3);font-family:monospace">SELL PRESSURE</div><div style="font-size:18px;font-weight:800;color:var(--red);margin-top:4px">'+sig.sell_count+'</div></div>';
    h+='</div>';
    h+='<div class="whales-title">🐋 WHALES BUYING ('+buyW.length+')</div>';
    h+='<div class="whale-chips">';
    buyW.slice(0,20).forEach(w=>{h+='<span class="whale-chip">'+w+'</span>';});
    h+='</div>';
    h+='<div class="actions"><a href="'+sig.dex_url+'" target="_blank" class="action ape">🦍 APE NOW</a>';
    h+='<a href="https://dexscreener.com/solana/'+sig.mint+'" target="_blank" class="action">📊 DEXSCREENER</a>';
    h+='<a href="https://solscan.io/token/'+sig.mint+'" target="_blank" class="action">◎ SOLSCAN</a>';
    h+='<a href="https://pump.fun/'+sig.mint+'" target="_blank" class="action">💊 PUMP.FUN</a></div></div></div>';
  });
  document.getElementById('hotList').innerHTML=h;
}
function renderSignals(){
  let sigs=allSignals;
  if(currentFilter!=='all') sigs=sigs.filter(s=>s.signal===currentFilter);
  if(!sigs||sigs.length===0){document.getElementById('signalsList').innerHTML='<div class="empty">No signals match. Click SCAN NEXT for more whales.</div>';return}
  let h='';
  sigs.forEach((sig,idx)=>{
    const coin=(sig.coin||'').replace(/\$+/g,'$');
    const buyW=[...new Set(sig.buy_whales||[])];
    h+='<div class="sig-card" id="sig-'+idx+'">';
    h+='<div class="sig-head" onclick="document.getElementById(\'sig-'+idx+'\').classList.toggle(\'open\')">';
    h+='<div class="sig-icon">◎</div>';
    h+='<div class="sig-info"><div class="sig-name">'+coin+'</div>';
    h+='<div class="sig-meta">🟢 '+sig.buy_count+' buys · 🔴 '+sig.sell_count+' sells · '+buyW.length+' whales</div></div>';
    h+='<div class="sig-grade" style="background:'+sig.signal_color+'20;color:'+sig.signal_color+'">'+sig.score+'<br><span style="font-size:8px;letter-spacing:1px">'+sig.signal+'</span></div>';
    h+='<div class="sig-toggle">▼</div></div>';
    h+='<div class="sig-body"><div class="whales-title">🐋 WHALES BUYING ('+buyW.length+')</div>';
    h+='<div class="whale-chips">';
    buyW.slice(0,20).forEach(w=>{h+='<span class="whale-chip">'+w+'</span>';});
    h+='</div>';
    h+='<div class="actions"><a href="'+sig.dex_url+'" target="_blank" class="action ape">🦍 APE NOW</a>';
    h+='<a href="https://dexscreener.com/solana/'+sig.mint+'" target="_blank" class="action">📊 DEXSCREENER</a>';
    h+='<a href="https://solscan.io/token/'+sig.mint+'" target="_blank" class="action">◎ SOLSCAN</a>';
    h+='<a href="https://pump.fun/'+sig.mint+'" target="_blank" class="action">💊 PUMP.FUN</a></div></div></div>';
  });
  document.getElementById('signalsList').innerHTML=h;
}


async function loadHoldings(wallet, label){
  const modal=document.getElementById('holdingsModal');
  const body=document.getElementById('modalBody');
  document.getElementById('modalTitle').textContent=label+' — Holdings';
  body.innerHTML='<div class="empty">🔍 Loading holdings... (~10s)</div>';
  modal.style.display='flex';
  try{
    const r=await fetch('/whale_portfolio.php?wallet='+wallet);
    const d=await r.json();
    if(!d.success){body.innerHTML='<div class="empty">'+(d.error||'No holdings found')+'</div>';return}
    let h='<div style="text-align:center;margin-bottom:18px;padding:16px;background:rgba(20,241,149,.05);border-radius:12px;border:1px solid rgba(20,241,149,.2)">';
    h+='<div style="font-size:10px;color:var(--text3);font-family:monospace;letter-spacing:2px">TOTAL PORTFOLIO VALUE</div>';
    h+='<div style="font-size:32px;font-weight:800;color:var(--sol-green);margin-top:6px">$'+d.total_value.toLocaleString()+'</div>';
    h+='<div style="font-size:11px;color:var(--text3);margin-top:4px">'+d.token_count+' tokens · '+d.wallet_short+'</div></div>';
    h+='<div class="whales-title">💰 HOLDINGS</div>';
    d.holdings.forEach(t=>{
      h+='<div style="display:flex;justify-content:space-between;align-items:center;padding:12px;border-bottom:1px solid rgba(255,255,255,.04)">';
      h+='<div><div style="font-weight:700;font-size:14px">'+t.symbol+'</div>';
      h+='<div style="font-size:10px;color:var(--text3);font-family:monospace">'+t.balance.toLocaleString()+' tokens</div></div>';
      h+='<div style="text-align:right"><div style="font-weight:700;color:var(--sol-green)">$'+t.value_usd.toLocaleString()+'</div>';
      h+='<a href="'+t.dex_url+'" target="_blank" style="font-size:10px;color:var(--sol-purple);text-decoration:none">📊 Chart</a></div></div>';
    });
    h+='<a href="'+d.solscan+'" target="_blank" class="action" style="display:block;text-align:center;margin-top:16px">◎ View on Solscan</a>';
    body.innerHTML=h;
  }catch(e){console.error(e);body.innerHTML='<div class="empty">Error loading holdings.</div>'}
}

async function loadDiscovery(force){
  try{
    document.getElementById('discoveryList').innerHTML='<div class="empty">Loading 300 whales...</div>';
    const r=await fetch('/whale_list.php'+(force?'?t='+Date.now():''));
    const d=await r.json();
    if(!d.success){document.getElementById('discoveryList').innerHTML='<div class="empty">No whale data.</div>';return}
    document.getElementById('discTotal').textContent=d.total||300;
    document.getElementById('discShowing').textContent=d.whales.length;
    document.getElementById('discCoins').textContent=d.whales.reduce((s,w)=>s+w.coins_count,0);
    let h='';
    d.whales.forEach(w=>{
      const ago=w.last_seen?Math.floor((Date.now()/1000-w.last_seen)/3600):0;
      h+='<div class="whale-row"><div class="wr-icon">🐋</div>';
      h+='<div class="wr-info"><div class="wr-label">'+w.label+'</div>';
      h+='<div class="wr-meta">'+w.coins_count+' coins caught · seen '+(ago<24?ago+'h':Math.floor(ago/24)+'d')+' ago</div>';
      h+='<div style="font-size:9px;color:var(--text3);font-family:monospace;margin-top:2px">'+w.address.slice(0,8)+'...'+w.address.slice(-6)+'</div></div>';
      h+='<div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end">';
      h+='<div class="wr-score">Score '+w.score+'</div>';
      h+='<button onclick="loadHoldings(\''+w.address+'\',\''+w.label+'\')" style="font-size:10px;padding:4px 10px;background:rgba(153,69,255,.12);border:1px solid rgba(153,69,255,.3);color:var(--sol-purple);border-radius:6px;cursor:pointer;white-space:nowrap">👁️ Holdings</button></div></div>';
    });
    document.getElementById('discoveryList').innerHTML=h;
  }catch(e){console.error(e);document.getElementById('discoveryList').innerHTML='<div class="empty">Error loading whales.</div>'}
}

function renderDiscoveryFromSignals(){
  // Aggregate whales from all signals
  let whaleMap={};
  allSignals.forEach(sig=>{
    (sig.buy_whales||[]).forEach(w=>{
      if(!whaleMap[w]) whaleMap[w]={label:w,coins:0,score:100};
      whaleMap[w].coins++;
    });
  });
  let whales=Object.values(whaleMap).sort((a,b)=>b.coins-a.coins);
  document.getElementById('discShowing').textContent=whales.length;
  document.getElementById('discCoins').textContent=whales.reduce((s,w)=>s+w.coins,0);
  if(whales.length===0){document.getElementById('discoveryList').innerHTML='<div class="empty">Load Alpha Signals first, then refresh here.</div>';return}
  let h='';
  whales.forEach(w=>{
    h+='<div class="whale-row"><div class="wr-icon">🐋</div>';
    h+='<div class="wr-info"><div class="wr-label">'+w.label+'</div>';
    h+='<div class="wr-meta">Active in '+w.coins+' alpha signal(s)</div></div>';
    h+='<div class="wr-score">Score '+w.score+'</div></div>';
  });
  document.getElementById('discoveryList').innerHTML=h;
}

document.addEventListener('click',function(e){
  if(!e.target||!e.target.classList) return;
  if(e.target.classList.contains('fbtn')){
    document.querySelectorAll('.fbtn').forEach(b=>b.classList.remove('active'));
    e.target.classList.add('active');
    currentFilter=e.target.dataset.filter;
    renderSignals();
  }
  if(e.target.classList.contains('tab')){
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    e.target.classList.add('active');
    const tab=e.target.dataset.tab;
    document.getElementById('tab-'+tab).classList.add('active');
    if(tab==='whales'&&!window.discLoaded){window.discLoaded=true;loadDiscovery();}
    if(tab==='live'&&!window.liveLoaded){window.liveLoaded=true;loadLive(false);}
    if(tab==='top'&&!window.topLoaded){window.topLoaded=true;loadTopWhales();}
    if(tab==='cex'&&!window.cexLoaded){window.cexLoaded=true;loadCexRadar();}
  }
});

loadTopWhales();
window.topLoaded=true;
loadLive(false);
window.liveLoaded=true;
startLiveTimer();
</script>


<div id="holdingsModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:1000;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)this.style.display='none'">
  <div style="background:var(--card);border:1px solid rgba(153,69,255,.3);border-radius:16px;max-width:500px;width:100%;max-height:80vh;overflow-y:auto;padding:24px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
      <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700" id="modalTitle">Whale Holdings</div>
      <button onclick="document.getElementById('holdingsModal').style.display='none'" style="background:rgba(255,71,87,.1);border:1px solid rgba(255,71,87,.2);color:var(--red);border-radius:8px;padding:6px 12px;cursor:pointer">✕</button>
    </div>
    <div id="modalBody"><div class="empty">Loading...</div></div>
  </div>
</div>

</body>
</html>
