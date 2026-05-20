<?php
session_start();
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
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_destroy();
    header('Location: /trading.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ETH Whale Tracker - NexAI</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600;700;800&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
--rainbow:linear-gradient(135deg,#FF6B6B,#FF9F43,#FFD32A,#00E676,#00D2FF,#7C4DFF);
--bg:#08080F;--card:#12121E;--card2:#181828;--text:#F0F0F8;--text2:#9B9BB0;--text3:#5B5B72;
--border:rgba(255,255,255,.05);--eth:#627EEA;--green:#00E676;--red:#FF4757;--amber:#FFD32A;--orange:#FF9F43;
}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;padding:16px;min-height:100vh;line-height:1.5}
.container{max-width:1400px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;background:var(--card);border:1px solid var(--border);border-radius:14px;margin-bottom:18px;flex-wrap:wrap;gap:14px}
.header h1{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;display:flex;align-items:center;gap:12px}
.logo-icon{width:36px;height:36px;border-radius:10px;background:var(--rainbow);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#fff}
.header-links{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.header-links a,.header-links button{padding:8px 14px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text2);font-size:11px;text-decoration:none;font-family:'JetBrains Mono',monospace;letter-spacing:1px;cursor:pointer;transition:all .2s}
.header-links a:hover{color:var(--eth);border-color:rgba(98,126,234,.3)}
.logout{background:rgba(255,71,87,.08)!important;color:var(--red)!important;border-color:rgba(255,71,87,.2)!important}

.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.s-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:16px 18px;position:relative;overflow:hidden}
.s-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.s-card.c1::after{background:var(--eth)}
.s-card.c2::after{background:var(--green)}
.s-card.c3::after{background:var(--amber)}
.s-card.c4::after{background:var(--red)}
.s-lab{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text3);letter-spacing:1.5px;margin-bottom:6px}
.s-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:700}
.s-val.eth{color:var(--eth)}
.s-val.green{color:var(--green)}
.s-val.amber{color:var(--amber)}
.s-val.red{color:var(--red)}
.s-sub{font-size:10px;color:var(--text3);margin-top:3px;font-family:monospace}

.panel{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:18px}
.p-head{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.p-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:600;display:flex;align-items:center;gap:8px}
.p-title .dot{width:8px;height:8px;border-radius:50%;background:var(--eth);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.refresh-btn{background:rgba(98,126,234,.1);border:1px solid rgba(98,126,234,.2);color:var(--eth);padding:6px 12px;border-radius:6px;font-size:10px;cursor:pointer;font-family:'JetBrains Mono',monospace;letter-spacing:1px}

.filters{padding:12px 18px;background:rgba(0,0,0,.2);border-bottom:1px solid var(--border);display:flex;gap:6px;flex-wrap:wrap}
.fbtn{padding:6px 12px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;color:var(--text2);font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:1px;cursor:pointer;transition:all .2s}
.fbtn:hover{border-color:var(--eth);color:var(--eth)}
.fbtn.active{background:rgba(0,230,118,.15);border-color:var(--green);color:var(--green)}

.coin-card{border-bottom:1px solid rgba(255,255,255,.03);transition:background .2s}
.coin-card:hover{background:rgba(255,255,255,.015)}
.cc-head{padding:14px 22px;display:grid;grid-template-columns:auto 1fr auto auto auto;gap:14px;align-items:center;cursor:pointer}
.cc-icon{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,rgba(98,126,234,.15),rgba(98,126,234,.05));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--eth)}
.cc-info{min-width:0}
.cc-name{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.cc-age{font-size:9px;padding:3px 8px;border-radius:4px;background:rgba(0,210,255,.1);color:#00D2FF;font-family:monospace;letter-spacing:1px}
.cc-meta{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text3);margin-top:3px}
.cc-safety{font-family:'Syne',sans-serif;font-weight:800;padding:8px 14px;border-radius:10px;font-size:13px;text-align:center;min-width:90px}
.cc-change{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;padding:6px 12px;border-radius:8px;text-align:center;min-width:70px}
.cc-change.up{background:rgba(0,230,118,.1);color:var(--green)}
.cc-change.down{background:rgba(255,71,87,.1);color:var(--red)}
.cc-toggle{font-size:18px;color:var(--text3);transition:transform .2s;padding:4px}
.coin-card.open .cc-toggle{transform:rotate(180deg)}
.cc-body{display:none;padding:0 22px 18px;background:rgba(0,0,0,.15)}
.coin-card.open .cc-body{display:block}

.safety-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;margin:14px 0}
.check{display:flex;align-items:center;gap:10px;padding:10px 12px;background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:8px}
.check.pass{border-color:rgba(0,230,118,.2);background:rgba(0,230,118,.04)}
.check.fail{border-color:rgba(255,71,87,.2);background:rgba(255,71,87,.04)}
.check-icon{width:24px;height:24px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
.check-icon.pass{background:rgba(0,230,118,.15);color:var(--green)}
.check-icon.fail{background:rgba(255,71,87,.15);color:var(--red)}
.check-info{min-width:0;flex:1}
.check-lab{font-size:11px;color:var(--text);font-weight:500}
.check-val{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);margin-top:1px}

.whales-section{margin-top:14px}
.whales-title{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text3);letter-spacing:2px;margin-bottom:10px}
.whale-row{display:grid;grid-template-columns:auto 1fr auto auto;gap:12px;align-items:center;padding:10px 14px;background:rgba(255,255,255,.02);border-left:3px solid;border-radius:8px;margin-bottom:5px;text-decoration:none;color:var(--text)}
.whale-row.holding{border-left-color:var(--green)}
.whale-row.dumping{border-left-color:var(--red)}
.whale-row.flipping{border-left-color:var(--amber)}
.wr-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700}
.wr-icon.holding{background:rgba(0,230,118,.1);color:var(--green)}
.wr-icon.dumping{background:rgba(255,71,87,.1);color:var(--red)}
.wr-icon.flipping{background:rgba(255,211,42,.1);color:var(--amber)}
.wr-info{min-width:0}
.wr-wallet{font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600}
.wr-tag{font-size:9px;padding:1px 6px;border-radius:3px;background:rgba(255,255,255,.05);color:var(--text3);letter-spacing:1px;text-transform:uppercase;margin-left:4px}
.wr-meta{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);margin-top:2px}
.wr-net{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;text-align:right}
.wr-net.pos{color:var(--green)}
.wr-net.neg{color:var(--red)}
.wr-time{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);text-align:right;min-width:50px}

.cc-actions{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}
.cc-action{padding:8px 14px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text2);font-family:'JetBrains Mono',monospace;font-size:10px;text-decoration:none;letter-spacing:1px;transition:all .2s}
.cc-action:hover{border-color:var(--eth);color:var(--eth)}
.cc-action.buy{background:linear-gradient(135deg,rgba(0,230,118,.1),rgba(0,210,255,.1));border-color:rgba(0,230,118,.3);color:var(--green)}

.empty{padding:40px 22px;text-align:center;color:var(--text3);font-size:13px}

@media(max-width:768px){
  body{padding:10px}
  .header h1{font-size:16px}
  .stats{grid-template-columns:1fr 1fr;gap:8px}
  .s-card{padding:12px}
  .s-val{font-size:18px}
  .cc-head{grid-template-columns:auto 1fr auto;padding:12px 14px;gap:10px}
  .cc-icon{width:36px;height:36px;font-size:14px}
  .cc-change{display:none}
  .cc-name{font-size:13px}
  .cc-meta{font-size:10px}
  .cc-safety{font-size:11px;padding:6px 10px;min-width:70px}
  .cc-body{padding:0 14px 14px}
  .safety-grid{grid-template-columns:1fr 1fr;gap:6px}
  .check{padding:8px 10px;gap:8px}
  .check-icon{width:20px;height:20px;font-size:11px}
  .check-lab{font-size:10px}
  .check-val{font-size:9px}
  .whale-row{grid-template-columns:auto 1fr auto;gap:8px;padding:8px 10px}
  .wr-time{display:none}
  .wr-wallet{font-size:11px}
  .wr-meta{font-size:9px}
  .wr-net{font-size:11px}
}
</style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1>
      <div class="logo-icon">Ξ</div>
      ETH Whale + Safety Tracker
    </h1>
    <div class="header-links">
      <a href="/whale.html" target="_blank">🐋 Public</a>
      <a href="/admin.php">🔮 Oracle</a>
      <a href="/">CallGod</a>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="logout">
        <button class="logout">Logout</button>
      </form>
    </div>
  </div>

  <div class="stats">
    <div class="s-card c1">
      <div class="s-lab">NEW ETH COINS</div>
      <div class="s-val eth" id="statCoins">—</div>
      <div class="s-sub">&lt; 72h old</div>
    </div>
    <div class="s-card c2">
      <div class="s-lab">🛡️ SAFE COINS</div>
      <div class="s-val green" id="statSafe">—</div>
      <div class="s-sub">9+/10 checks</div>
    </div>
    <div class="s-card c3">
      <div class="s-lab">🐋 TOTAL WHALES</div>
      <div class="s-val amber" id="statWhales">—</div>
      <div class="s-sub">$1K+ positions</div>
    </div>
    <div class="s-card c4">
      <div class="s-lab">⚠️ RISKY COINS</div>
      <div class="s-val red" id="statRisky">—</div>
      <div class="s-sub">Avoid these</div>
    </div>
  </div>

  <div class="panel">
    <div class="p-head">
      <div class="p-title">
        <div class="dot"></div>
        <span>Ξ New ETH Coins · Safety Checks · Whale Activity</span>
      </div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <span id="updateTime" style="font-size:10px;color:var(--text3);font-family:monospace"></span>
        <button class="refresh-btn" onclick="loadData(true)">🔄 REFRESH</button>
      </div>
    </div>
    <div class="filters">
      <button class="fbtn active" data-filter="all">ALL</button>
      <button class="fbtn" data-filter="SAFE">🟢 SAFE ONLY</button>
      <button class="fbtn" data-filter="OK">🟡 OK</button>
      <button class="fbtn" data-filter="RISKY">🟠 RISKY</button>
      <button class="fbtn" data-filter="DANGER">🔴 DANGER</button>
    </div>
    <div id="coinsList">
      <div class="empty">Loading ETH new coins + safety checks...</div>
    </div>
  </div>
</div>

<script>
let allCoins=[];
let currentFilter='all';

async function loadData(force){
  try{
    const url='/new_coin_whales.php'+(force?'?t='+Date.now():'');
    const r=await fetch(url);
    const d=await r.json();
    if(!d.success){
      document.getElementById('coinsList').innerHTML='<div class="empty">Loading failed. Try refresh.</div>';
      return;
    }
    const s=d.stats;
    document.getElementById('statCoins').textContent=s.total_coins||0;
    document.getElementById('statSafe').textContent=s.safe_coins||0;
    document.getElementById('statWhales').textContent=s.total_whales||0;
    document.getElementById('statRisky').textContent=s.risky_coins||0;
    document.getElementById('updateTime').textContent='Updated: '+d.updated_at;
    
    allCoins=d.coins||[];
    renderCoins();
  }catch(e){
    console.error('Trading panel error:',e);
    document.getElementById('coinsList').innerHTML='<div class="empty">Error: '+e.message+'</div>';
  }
}

function renderCoins(){
  let coins=allCoins;
  if(currentFilter!=='all') coins=coins.filter(c=>c.safety_grade===currentFilter);
  
  if(!coins || coins.length===0){
    document.getElementById('coinsList').innerHTML='<div class="empty">No coins match this filter. Try ALL.</div>';
    return;
  }
  
  let h='';
  coins.forEach((c,idx)=>{
    const changeCls=c.change_1h>=0?'up':'down';
    const changeSign=c.change_1h>=0?'+':'';
    
    let checksHtml='';
    Object.entries(c.safety_checks).forEach(([key,chk])=>{
      const cls=chk.pass?'pass':'fail';
      const icon=chk.pass?'✓':'✗';
      checksHtml+='<div class="check '+cls+'"><div class="check-icon '+cls+'">'+icon+'</div><div class="check-info"><div class="check-lab">'+chk.label+'</div><div class="check-val">'+chk.value+'</div></div></div>';
    });
    
    let whalesHtml='';
    if(c.whales && c.whales.length>0){
      c.whales.forEach(w=>{
        const typeIcon=w.type==='holding'?'↑':(w.type==='dumping'?'↓':'⇄');
        const netCls=w.net_amount>=0?'pos':'neg';
        const netSign=w.net_amount>=0?'+':'-';
        whalesHtml+='<a href="'+w.explorer_url+'" target="_blank" class="whale-row '+w.type+'">';
        whalesHtml+='<div class="wr-icon '+w.type+'">'+typeIcon+'</div>';
        whalesHtml+='<div class="wr-info">';
        whalesHtml+='<div class="wr-wallet">'+w.wallet+'<span class="wr-tag">'+w.type+'</span></div>';
        whalesHtml+='<div class="wr-meta">🟢 '+w.count_buys+' buys · 🔴 '+w.count_sells+' sells · last: '+w.last_action+'</div>';
        whalesHtml+='</div>';
        whalesHtml+='<div class="wr-net '+netCls+'">'+netSign+w.net_usd_formatted+'</div>';
        whalesHtml+='<div class="wr-time">'+w.time_display+'</div>';
        whalesHtml+='</a>';
      });
    } else {
      whalesHtml='<div class="empty" style="padding:20px">No whales detected yet.</div>';
    }
    
    h+='<div class="coin-card" id="cc-'+idx+'">';
    h+='<div class="cc-head" onclick="document.getElementById(\'cc-'+idx+'\').classList.toggle(\'open\')">';
    h+='<div class="cc-icon">'+c.symbol.charAt(0)+'</div>';
    h+='<div class="cc-info">';
    h+='<div class="cc-name">$'+c.symbol+' <span class="cc-age">'+c.age_display+' old</span></div>';
    h+='<div class="cc-meta">'+c.price_formatted+' · MC: '+c.mc_formatted+' · Liq: '+c.liquidity_formatted+' · 🐋 '+c.total_whales+'</div>';
    h+='</div>';
    h+='<div class="cc-safety" style="background:'+c.safety_color+'20;color:'+c.safety_color+';border:1px solid '+c.safety_color+'40">'+c.safety_score+'/10<br><span style="font-size:9px;letter-spacing:1px">'+c.safety_grade+'</span></div>';
    h+='<div class="cc-change '+changeCls+'">'+changeSign+c.change_1h+'%</div>';
    h+='<div class="cc-toggle">▼</div>';
    h+='</div>';
    h+='<div class="cc-body">';
    h+='<div class="whales-title">🛡️ SAFETY CHECKS ('+c.safety_score+'/10 · '+c.safety_grade+')</div>';
    h+='<div class="safety-grid">'+checksHtml+'</div>';
    h+='<div class="whales-section">';
    h+='<div class="whales-title">🐋 TOP WHALES — '+c.holders_count+' HOLDING / '+c.dumpers_count+' DUMPING</div>';
    h+=whalesHtml;
    h+='</div>';
    h+='<div class="cc-actions">';
    h+='<a href="'+c.dex_url+'" target="_blank" class="cc-action buy">💰 BUY ON DEX</a>';
    h+='<a href="https://etherscan.io/token/'+c.address+'" target="_blank" class="cc-action">Ξ ETHERSCAN</a>';
    h+='<a href="https://dexscreener.com/ethereum/'+c.address+'" target="_blank" class="cc-action">📊 DEXSCREENER</a>';
    h+='<a href="https://gopluslabs.io/token-security/1/'+c.address+'" target="_blank" class="cc-action">🛡️ GOPLUS DETAILS</a>';
    h+='</div>';
    h+='</div>';
    h+='</div>';
  });
  document.getElementById('coinsList').innerHTML=h;
}

document.addEventListener('click',function(e){
  if(e.target && e.target.classList && e.target.classList.contains('fbtn')){
    document.querySelectorAll('.fbtn').forEach(b=>b.classList.remove('active'));
    e.target.classList.add('active');
    currentFilter=e.target.dataset.filter;
    renderCoins();
  }
});

loadData();
setInterval(()=>loadData(false),180000);
</script>

</body>
</html>
