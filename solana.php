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
        </form></body></html>
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
    <button class="tab active" data-tab="signals">🎯 Alpha Signals</button>
    <button class="tab" data-tab="whales">🐋 Whale Discovery</button>
  </div>

  <!-- TAB: ALPHA SIGNALS -->
  <div class="tab-content active" id="tab-signals">
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
let allSignals=[];
let currentFilter='all';
window.solBatch=0;

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
    renderSignals();
  }catch(e){console.error(e);document.getElementById('signalsList').innerHTML='<div class="empty">Error loading.</div>'}
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

async function loadDiscovery(force){
  try{
    document.getElementById('discoveryList').innerHTML='<div class="empty">Loading whales...</div>';
    const url='/discover_whales.php'+(force?'?t='+Date.now():'');
    const r=await fetch(url);
    const d=await r.json();
    // discover_whales returns discovery status, need the actual whale data
    // Fetch from a helper - use total_discovered
    document.getElementById('discTotal').textContent=d.total_discovered||300;
    // Load whale details from discovered data via signals whale list
    renderDiscoveryFromSignals();
  }catch(e){console.error(e);document.getElementById('discoveryList').innerHTML='<div class="empty">Discovery data via Alpha Signals tab.</div>'}
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
    if(tab==='whales') renderDiscoveryFromSignals();
  }
});

loadSignals();
</script>

</body>
</html>
