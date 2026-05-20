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
<title>ETH Whale Intelligence - NexAI</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600;700;800&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
--rainbow:linear-gradient(135deg,#FF6B6B,#FF9F43,#FFD32A,#00E676,#00D2FF,#7C4DFF);
--bg:#08080F;--card:#12121E;--text:#F0F0F8;--text2:#9B9BB0;--text3:#5B5B72;
--border:rgba(255,255,255,.05);--eth:#627EEA;--green:#00E676;--red:#FF4757;--amber:#FFD32A;--purple:#7C4DFF;--orange:#FF9F43;
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

.tabs{display:flex;gap:6px;background:var(--card);border:1px solid var(--border);border-radius:14px;padding:8px;margin-bottom:18px;overflow-x:auto}
.tab{flex:1;min-width:140px;padding:12px 18px;background:transparent;border:1px solid transparent;border-radius:10px;color:var(--text2);font-family:'Syne',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap;text-align:center}
.tab:hover{color:var(--text)}
.tab.active{background:rgba(98,126,234,.1);border-color:rgba(98,126,234,.3);color:var(--eth)}
.tab[data-tab="prepump"].active{background:rgba(255,71,87,.1);border-color:rgba(255,71,87,.3);color:var(--red)}
.tab[data-tab="whales"].active{background:rgba(124,77,255,.1);border-color:rgba(124,77,255,.3);color:var(--purple)}
.tab-content{display:none}
.tab-content.active{display:block}

.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.s-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:16px 18px;position:relative;overflow:hidden}
.s-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.s-card.c1::after{background:var(--eth)}
.s-card.c2::after{background:var(--green)}
.s-card.c3::after{background:var(--amber)}
.s-card.c4::after{background:var(--red)}
.s-card.c5::after{background:var(--purple)}
.s-card.c6::after{background:var(--orange)}
.s-lab{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text3);letter-spacing:1.5px;margin-bottom:6px}
.s-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:700}
.s-val.eth{color:var(--eth)}.s-val.green{color:var(--green)}.s-val.amber{color:var(--amber)}.s-val.red{color:var(--red)}.s-val.purple{color:var(--purple)}.s-val.orange{color:var(--orange)}
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

.coin-card,.profile-card,.pump-card{border-bottom:1px solid rgba(255,255,255,.03);transition:background .2s}
.coin-card:hover{background:rgba(255,255,255,.015)}
.profile-card:hover{background:rgba(124,77,255,.02)}
.pump-card:hover{background:rgba(255,71,87,.03)}

.cc-head,.pc-head,.pump-head{padding:14px 22px;display:grid;gap:14px;align-items:center;cursor:pointer}
.cc-head{grid-template-columns:auto 1fr auto auto auto}
.pc-head{grid-template-columns:auto 1fr auto auto auto}
.pump-head{grid-template-columns:auto 1fr auto auto auto}

.cc-icon,.pump-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:18px;font-weight:700}
.cc-icon{background:linear-gradient(135deg,rgba(98,126,234,.15),rgba(98,126,234,.05));color:var(--eth)}
.pump-icon{background:linear-gradient(135deg,rgba(255,71,87,.15),rgba(255,159,67,.05));color:var(--red);position:relative}
.pc-icon{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,rgba(124,77,255,.2),rgba(0,210,255,.1));display:flex;align-items:center;justify-content:center;font-size:22px}

.cc-info,.pc-info,.pump-info{min-width:0}
.cc-name,.pc-name,.pump-name{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.cc-age,.pump-age{font-size:9px;padding:3px 8px;border-radius:4px;background:rgba(0,210,255,.1);color:#00D2FF;font-family:monospace;letter-spacing:1px}
.pump-src{font-size:8px;padding:2px 6px;border-radius:3px;background:rgba(255,255,255,.05);color:var(--text3);font-family:monospace;letter-spacing:1px}
.pc-label{font-size:10px;padding:3px 8px;border-radius:4px;background:rgba(124,77,255,.15);color:var(--purple);font-family:monospace;letter-spacing:1px;text-transform:uppercase}
.cc-meta,.pc-meta,.pump-meta{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text3);margin-top:3px}

.cc-safety,.pump-grade{font-family:'Syne',sans-serif;font-weight:800;padding:8px 14px;border-radius:10px;font-size:13px;text-align:center;min-width:110px}
.cc-change,.pump-change{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;padding:6px 12px;border-radius:8px;text-align:center;min-width:70px}
.cc-change.up,.pump-change.up{background:rgba(0,230,118,.1);color:var(--green)}
.cc-change.down,.pump-change.down{background:rgba(255,71,87,.1);color:var(--red)}
.pc-portfolio{text-align:center;min-width:90px}
.pc-port-val{font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--green)}
.pc-port-lab{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text3);letter-spacing:1px;margin-top:2px}
.pc-winrate{font-family:'Syne',sans-serif;font-weight:800;padding:8px 12px;border-radius:10px;text-align:center;min-width:70px;font-size:14px}
.pc-winrate.high{background:rgba(0,230,118,.15);color:var(--green);border:1px solid rgba(0,230,118,.3)}
.pc-winrate.mid{background:rgba(255,211,42,.15);color:var(--amber);border:1px solid rgba(255,211,42,.3)}
.pc-winrate.low{background:rgba(255,71,87,.15);color:var(--red);border:1px solid rgba(255,71,87,.3)}
.cc-toggle,.pc-toggle,.pump-toggle{font-size:18px;color:var(--text3);transition:transform .2s;padding:4px}
.coin-card.open .cc-toggle,.profile-card.open .pc-toggle,.pump-card.open .pump-toggle{transform:rotate(180deg)}
.cc-body,.pc-body,.pump-body{display:none;padding:0 22px 18px;background:rgba(0,0,0,.15)}
.coin-card.open .cc-body,.profile-card.open .pc-body,.pump-card.open .pump-body{display:block}

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

.whales-title{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text3);letter-spacing:2px;margin-bottom:10px;margin-top:14px}

.signals-list{display:flex;flex-direction:column;gap:6px;margin-top:12px}
.signal-item{display:flex;align-items:center;gap:10px;padding:10px 14px;background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:8px;font-size:12px}
.signal-item.strong{border-color:rgba(255,71,87,.3);background:rgba(255,71,87,.05)}
.signal-icon{font-size:18px}
.signal-strong-badge{font-size:8px;padding:2px 6px;border-radius:3px;background:rgba(255,71,87,.2);color:var(--red);letter-spacing:1px;font-family:monospace;margin-left:auto}

.whale-row{display:grid;grid-template-columns:auto 1fr auto auto;gap:12px;align-items:center;padding:10px 14px;background:rgba(255,255,255,.02);border-left:3px solid;border-radius:8px;margin-bottom:5px;text-decoration:none;color:var(--text)}
.whale-row.holding{border-left-color:var(--green)}
.whale-row.dumping{border-left-color:var(--red)}
.whale-row.flipping{border-left-color:var(--amber)}
.wr-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700}
.wr-icon.holding{background:rgba(0,230,118,.1);color:var(--green)}
.wr-icon.dumping{background:rgba(255,71,87,.1);color:var(--red)}
.wr-icon.flipping{background:rgba(255,211,42,.1);color:var(--amber)}
.wr-wallet{font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600}
.wr-tag{font-size:9px;padding:1px 6px;border-radius:3px;background:rgba(255,255,255,.05);color:var(--text3);letter-spacing:1px;text-transform:uppercase;margin-left:4px}
.wr-meta{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);margin-top:2px}
.wr-net{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;text-align:right}
.wr-net.pos{color:var(--green)}.wr-net.neg{color:var(--red)}
.wr-time{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);text-align:right;min-width:50px}

.coin-list{display:flex;flex-direction:column;gap:5px;margin-top:10px}
.coin-row{display:grid;grid-template-columns:auto 1fr auto auto auto;gap:12px;align-items:center;padding:10px 14px;background:rgba(255,255,255,.02);border-radius:8px;border-left:3px solid;text-decoration:none;color:var(--text)}
.coin-row:hover{background:rgba(255,255,255,.04)}
.cr-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0}
.cr-symbol{font-family:'Syne',sans-serif;font-size:13px;font-weight:600}
.cr-meta{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);margin-top:2px}
.cr-amount{text-align:right;font-family:'JetBrains Mono',monospace}
.cr-amount-v{font-size:12px;font-weight:500}
.cr-amount-l{font-size:9px;color:var(--text3);margin-top:1px}
.cr-change{font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;padding:4px 8px;border-radius:6px;text-align:center;min-width:60px}
.cr-change.up{background:rgba(0,230,118,.1);color:var(--green)}
.cr-change.down{background:rgba(255,71,87,.1);color:var(--red)}

.cc-actions{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}
.cc-action{padding:8px 14px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text2);font-family:'JetBrains Mono',monospace;font-size:10px;text-decoration:none;letter-spacing:1px;transition:all .2s}
.cc-action:hover{border-color:var(--eth);color:var(--eth)}
.cc-action.buy{background:linear-gradient(135deg,rgba(0,230,118,.1),rgba(0,210,255,.1));border-color:rgba(0,230,118,.3);color:var(--green)}
.cc-action.ape{background:linear-gradient(135deg,rgba(255,71,87,.15),rgba(255,159,67,.1));border-color:rgba(255,71,87,.3);color:var(--red);font-weight:700}

.empty{padding:40px 22px;text-align:center;color:var(--text3);font-size:13px}

@media(max-width:768px){
  body{padding:10px}
  .header h1{font-size:16px}
  .tabs{padding:6px}
  .tab{padding:10px 12px;font-size:11px;min-width:0}
  .stats{grid-template-columns:1fr 1fr;gap:8px}
  .s-card{padding:12px}
  .s-val{font-size:18px}
  .cc-head,.pc-head,.pump-head{grid-template-columns:auto 1fr auto;padding:12px 14px;gap:10px}
  .cc-icon,.pump-icon{width:36px;height:36px;font-size:14px}
  .pc-icon{width:38px;height:38px;font-size:16px}
  .cc-change,.pc-portfolio,.pump-change{display:none}
  .cc-name,.pc-name,.pump-name{font-size:13px}
  .cc-meta,.pc-meta,.pump-meta{font-size:10px}
  .cc-safety,.pump-grade{font-size:10px;padding:5px 8px;min-width:80px}
  .pc-winrate{font-size:12px;padding:6px 8px;min-width:55px}
  .cc-body,.pc-body,.pump-body{padding:0 14px 14px}
  .safety-grid{grid-template-columns:1fr 1fr;gap:6px}
  .check{padding:8px 10px;gap:8px}
  .check-icon{width:20px;height:20px;font-size:11px}
  .check-lab{font-size:10px}.check-val{font-size:9px}
  .whale-row{grid-template-columns:auto 1fr auto;gap:8px;padding:8px 10px}
  .wr-time{display:none}
  .coin-row{grid-template-columns:auto 1fr auto;gap:8px}
  .cr-amount{display:none}
}
</style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1><div class="logo-icon">Ξ</div>ETH Whale Intelligence</h1>
    <div class="header-links">
      <a href="/whale.html" target="_blank">🐋 Public</a>
      <a href="/admin.php">🔮 Oracle</a>
      <a href="/">CallGod</a>
      <form method="POST" style="display:inline"><input type="hidden" name="action" value="logout"><button class="logout">Logout</button></form>
    </div>
  </div>

  <div class="tabs">
    <button class="tab" data-tab="prepump">🔥 Pre-Pump Detector</button>
    <button class="tab active" data-tab="coins">🪙 New Coins + Safety</button>
    <button class="tab" data-tab="whales">🐋 Smart Money</button>
  </div>

  <!-- TAB: PRE-PUMP -->
  <div class="tab-content" id="tab-prepump">
    <div class="stats">
      <div class="s-card c4"><div class="s-lab">🔥 PUMP IMMINENT</div><div class="s-val red" id="ppImminent">—</div><div class="s-sub">90%+ probability</div></div>
      <div class="s-card c6"><div class="s-lab">🟠 HIGH ALERT</div><div class="s-val orange" id="ppHigh">—</div><div class="s-sub">70-85%</div></div>
      <div class="s-card c3"><div class="s-lab">🟡 STRONG SIGNAL</div><div class="s-val amber" id="ppStrong">—</div><div class="s-sub">55-70%</div></div>
      <div class="s-card c1"><div class="s-lab">🔵 EARLY SIGNAL</div><div class="s-val eth" id="ppEarly">—</div><div class="s-sub">40-55%</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot" style="background:var(--red)"></div><span>🔥 Pre-Pump Detector — Catch Pumps Before They Happen</span></div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <span id="ppMeta" style="font-size:10px;color:var(--text3);font-family:monospace"></span>
          <button class="refresh-btn" onclick="loadPrepump(true)" style="background:rgba(255,71,87,.1);border-color:rgba(255,71,87,.2);color:var(--red)">🔄 REFRESH</button>
        </div>
      </div>
      <div id="prepumpList"><div class="empty">Loading pre-pump signals...</div></div>
    </div>
  </div>

  <!-- TAB: NEW COINS -->
  <div class="tab-content active" id="tab-coins">
    <div class="stats">
      <div class="s-card c1"><div class="s-lab">NEW ETH COINS</div><div class="s-val eth" id="statCoins">—</div><div class="s-sub">&lt; 72h old</div></div>
      <div class="s-card c2"><div class="s-lab">🛡️ SAFE COINS</div><div class="s-val green" id="statSafe">—</div><div class="s-sub">9+/10 checks</div></div>
      <div class="s-card c3"><div class="s-lab">🐋 TOTAL WHALES</div><div class="s-val amber" id="statWhales">—</div><div class="s-sub">$1K+ positions</div></div>
      <div class="s-card c4"><div class="s-lab">⚠️ RISKY COINS</div><div class="s-val red" id="statRisky">—</div><div class="s-sub">Avoid these</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div><span>Ξ New ETH Coins · Safety · Whales</span></div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <span id="updateTime" style="font-size:10px;color:var(--text3);font-family:monospace"></span>
          <button class="refresh-btn" onclick="loadCoins(true)">🔄 REFRESH</button>
        </div>
      </div>
      <div class="filters">
        <button class="fbtn active" data-filter="all">ALL</button>
        <button class="fbtn" data-filter="SAFE">🟢 SAFE</button>
        <button class="fbtn" data-filter="OK">🟡 OK</button>
        <button class="fbtn" data-filter="RISKY">🟠 RISKY</button>
        <button class="fbtn" data-filter="DANGER">🔴 DANGER</button>
      </div>
      <div id="coinsList"><div class="empty">Loading...</div></div>
    </div>
  </div>

  <!-- TAB: SMART MONEY -->
  <div class="tab-content" id="tab-whales">
    <div class="stats">
      <div class="s-card c5"><div class="s-lab">SMART WALLETS</div><div class="s-val purple" id="profWallets">—</div><div class="s-sub">Tracked</div></div>
      <div class="s-card c2"><div class="s-lab">TOTAL HELD</div><div class="s-val green" id="profPortfolio">—</div><div class="s-sub">Combined</div></div>
      <div class="s-card c1"><div class="s-lab">COINS TRACKED</div><div class="s-val eth" id="profCoins">—</div><div class="s-sub">Last 7 days</div></div>
      <div class="s-card c3"><div class="s-lab">AVG WIN RATE</div><div class="s-val amber" id="profWinRate">—</div><div class="s-sub">All wallets</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot" style="background:var(--purple)"></div><span>🐋 Smart Money — What Each Whale Owns</span></div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <span id="profUpdateTime" style="font-size:10px;color:var(--text3);font-family:monospace"></span>
          <button class="refresh-btn" onclick="loadProfiles(true)" style="background:rgba(124,77,255,.1);border-color:rgba(124,77,255,.2);color:var(--purple)">🔄 REFRESH</button>
        </div>
      </div>
      <div id="profilesList"><div class="empty">Loading whale profiles... (first call takes 30-60s)</div></div>
    </div>
  </div>
</div>

<script>
let allCoins=[];
let allProfiles=[];
let allPumps=[];
let currentFilter='all';

async function loadCoins(force){
  try{
    const url='/new_coin_whales.php'+(force?'?t='+Date.now():'');
    const r=await fetch(url);
    const d=await r.json();
    if(!d.success) return;
    const s=d.stats;
    document.getElementById('statCoins').textContent=s.total_coins||0;
    document.getElementById('statSafe').textContent=s.safe_coins||0;
    document.getElementById('statWhales').textContent=s.total_whales||0;
    document.getElementById('statRisky').textContent=s.risky_coins||0;
    document.getElementById('updateTime').textContent='Updated: '+d.updated_at;
    allCoins=d.coins||[];
    renderCoins();
  }catch(e){console.error(e)}
}

function renderCoins(){
  let coins=allCoins;
  if(currentFilter!=='all') coins=coins.filter(c=>c.safety_grade===currentFilter);
  if(!coins||coins.length===0){document.getElementById('coinsList').innerHTML='<div class="empty">No coins match.</div>';return}
  let h='';
  coins.forEach((c,idx)=>{
    const changeCls=c.change_1h>=0?'up':'down';
    const changeSign=c.change_1h>=0?'+':'';
    let checksHtml='';
    Object.entries(c.safety_checks).forEach(([key,chk])=>{
      const cls=chk.pass?'pass':'fail';
      checksHtml+='<div class="check '+cls+'"><div class="check-icon '+cls+'">'+(chk.pass?'✓':'✗')+'</div><div class="check-info"><div class="check-lab">'+chk.label+'</div><div class="check-val">'+chk.value+'</div></div></div>';
    });
    let whalesHtml='';
    if(c.whales&&c.whales.length>0){
      c.whales.forEach(w=>{
        const ti=w.type==='holding'?'↑':(w.type==='dumping'?'↓':'⇄');
        const nc=w.net_amount>=0?'pos':'neg';
        const ns=w.net_amount>=0?'+':'-';
        whalesHtml+='<a href="'+w.explorer_url+'" target="_blank" class="whale-row '+w.type+'">';
        whalesHtml+='<div class="wr-icon '+w.type+'">'+ti+'</div>';
        whalesHtml+='<div><div class="wr-wallet">'+w.wallet+'<span class="wr-tag">'+w.type+'</span></div>';
        whalesHtml+='<div class="wr-meta">🟢 '+w.count_buys+' buys · 🔴 '+w.count_sells+' sells</div></div>';
        whalesHtml+='<div class="wr-net '+nc+'">'+ns+w.net_usd_formatted+'</div>';
        whalesHtml+='<div class="wr-time">'+w.time_display+'</div></a>';
      });
    } else whalesHtml='<div class="empty" style="padding:20px">No whales yet.</div>';
    h+='<div class="coin-card" id="cc-'+idx+'">';
    h+='<div class="cc-head" onclick="document.getElementById(\'cc-'+idx+'\').classList.toggle(\'open\')">';
    h+='<div class="cc-icon">'+c.symbol.charAt(0)+'</div>';
    h+='<div class="cc-info"><div class="cc-name">$'+c.symbol+' <span class="cc-age">'+c.age_display+'</span></div>';
    h+='<div class="cc-meta">'+c.price_formatted+' · MC: '+c.mc_formatted+' · Liq: '+c.liquidity_formatted+' · 🐋 '+c.total_whales+'</div></div>';
    h+='<div class="cc-safety" style="background:'+c.safety_color+'20;color:'+c.safety_color+';border:1px solid '+c.safety_color+'40">'+c.safety_score+'/10<br><span style="font-size:9px;letter-spacing:1px">'+c.safety_grade+'</span></div>';
    h+='<div class="cc-change '+changeCls+'">'+changeSign+c.change_1h+'%</div><div class="cc-toggle">▼</div></div>';
    h+='<div class="cc-body"><div class="whales-title">🛡️ SAFETY CHECKS</div><div class="safety-grid">'+checksHtml+'</div>';
    h+='<div class="whales-title">🐋 WHALES</div>'+whalesHtml;
    h+='<div class="cc-actions"><a href="'+c.dex_url+'" target="_blank" class="cc-action buy">💰 BUY ON DEX</a>';
    h+='<a href="https://etherscan.io/token/'+c.address+'" target="_blank" class="cc-action">Ξ ETHERSCAN</a>';
    h+='<a href="https://dexscreener.com/ethereum/'+c.address+'" target="_blank" class="cc-action">📊 DEX</a>';
    h+='<a href="https://gopluslabs.io/token-security/1/'+c.address+'" target="_blank" class="cc-action">🛡️ GOPLUS</a></div></div></div>';
  });
  document.getElementById('coinsList').innerHTML=h;
}

async function loadProfiles(force){
  try{
    const url='/whale_profiles.php'+(force?'?t='+Date.now():'');
    document.getElementById('profilesList').innerHTML='<div class="empty">Loading... (30-60s)</div>';
    const r=await fetch(url);
    const d=await r.json();
    if(!d.success) return;
    const s=d.stats;
    document.getElementById('profWallets').textContent=s.total_wallets||0;
    document.getElementById('profPortfolio').textContent=s.total_portfolio>=1e6?'$'+(s.total_portfolio/1e6).toFixed(2)+'M':'$'+(s.total_portfolio/1e3).toFixed(0)+'K';
    document.getElementById('profCoins').textContent=s.total_coins||0;
    document.getElementById('profWinRate').textContent=(s.avg_win_rate||0)+'%';
    document.getElementById('profUpdateTime').textContent='Updated: '+d.updated_at;
    allProfiles=d.profiles||[];
    renderProfiles();
  }catch(e){console.error(e)}
}

function renderProfiles(){
  if(!allProfiles||allProfiles.length===0){document.getElementById('profilesList').innerHTML='<div class="empty">No active wallets found.</div>';return}
  let h='';
  allProfiles.forEach((p,idx)=>{
    const winCls=p.win_rate>=70?'high':(p.win_rate>=50?'mid':'low');
    let coinsHtml='';
    p.coins.forEach(c=>{
      const changeCls=c.change_24h>=0?'up':'down';
      const changeSign=c.change_24h>=0?'+':'';
      const si=c.status==='HOLDING'?'💎':(c.status==='DUMPED'?'🔴':(c.status==='SOLD (TOP)'?'⚠️':'⇄'));
      coinsHtml+='<a href="'+c.dex_url+'" target="_blank" class="coin-row" style="border-left-color:'+c.status_color+'">';
      coinsHtml+='<div class="cr-icon" style="background:'+c.status_color+'20;color:'+c.status_color+'">'+si+'</div>';
      coinsHtml+='<div><div class="cr-symbol">$'+c.symbol+' <span class="wr-tag" style="background:'+c.status_color+'20;color:'+c.status_color+'">'+c.status+'</span></div>';
      coinsHtml+='<div class="cr-meta">'+c.first_buy_age+' ago · '+c.count_buys+'B/'+c.count_sells+'S</div></div>';
      coinsHtml+='<div class="cr-amount"><div class="cr-amount-v">'+c.bought_usd_formatted+'</div><div class="cr-amount-l">BOUGHT</div></div>';
      coinsHtml+='<div class="cr-amount"><div class="cr-amount-v" style="color:'+c.status_color+'">'+c.held_usd_formatted+'</div><div class="cr-amount-l">HELD</div></div>';
      coinsHtml+='<div class="cr-change '+changeCls+'">'+changeSign+c.change_24h+'%</div></a>';
    });
    h+='<div class="profile-card" id="pc-'+idx+'">';
    h+='<div class="pc-head" onclick="document.getElementById(\'pc-'+idx+'\').classList.toggle(\'open\')">';
    h+='<div class="pc-icon">🐋</div>';
    h+='<div class="pc-info"><div class="pc-name">'+p.wallet+' <span class="pc-label">'+p.discovered_from+'</span></div>';
    h+='<div class="pc-meta">'+p.coins_count+' coins · '+p.holding_count+' holding · '+p.wins+'W/'+p.losses+'L</div></div>';
    h+='<div class="pc-portfolio"><div class="pc-port-val">'+p.total_held_formatted+'</div><div class="pc-port-lab">HELD</div></div>';
    h+='<div class="pc-winrate '+winCls+'">'+p.win_rate+'%<br><span style="font-size:8px;letter-spacing:1px">WIN</span></div>';
    h+='<div class="pc-toggle">▼</div></div>';
    h+='<div class="pc-body"><div class="whales-title">📊 LAST 7 DAYS</div><div class="coin-list">'+coinsHtml+'</div>';
    h+='<div class="cc-actions"><a href="'+p.explorer_url+'" target="_blank" class="cc-action">Ξ ETHERSCAN</a>';
    h+='<a href="https://debank.com/profile/'+p.wallet_full+'" target="_blank" class="cc-action">📊 DEBANK</a></div></div></div>';
  });
  document.getElementById('profilesList').innerHTML=h;
}

async function loadPrepump(force){
  try{
    document.getElementById('prepumpList').innerHTML='<div class="empty">🔍 Scanning Uniswap V2/V3 + DexScreener... (~60s)</div>';
    const url='/prepump.php'+(force?'?t='+Date.now():'');
    const r=await fetch(url);
    const d=await r.json();
    if(!d.success) return;
    const s=d.stats;
    document.getElementById('ppImminent').textContent=s.pump_imminent||0;
    document.getElementById('ppHigh').textContent=s.high_alert||0;
    document.getElementById('ppStrong').textContent=s.strong_signal||0;
    document.getElementById('ppEarly').textContent=s.early_signal||0;
    document.getElementById('ppMeta').textContent='Block: '+(d.block_scanned||0).toLocaleString()+' · '+s.total_scanned+' scanned · '+s.total_candidates+' candidates';
    allPumps=d.coins||[];
    renderPrepump();
  }catch(e){console.error(e)}
}

function renderPrepump(){
  if(!allPumps||allPumps.length===0){
    document.getElementById('prepumpList').innerHTML='<div class="empty">No pre-pump signals yet. Auto-refresh every 90s. Try manual refresh.</div>';
    return;
  }
  let h='';
  allPumps.forEach((c,idx)=>{
    const changeCls=c.change_1h>=0?'up':'down';
    const changeSign=c.change_1h>=0?'+':'';
    let sigHtml='';
    c.signals.forEach(sig=>{
      const cls=sig.strong?'strong':'';
      sigHtml+='<div class="signal-item '+cls+'"><div class="signal-icon">'+sig.icon+'</div><div>'+sig.text+'</div>';
      if(sig.strong) sigHtml+='<div class="signal-strong-badge">⭐ STRONG</div>';
      sigHtml+='</div>';
    });
    h+='<div class="pump-card" id="pump-'+idx+'">';
    h+='<div class="pump-head" onclick="document.getElementById(\'pump-'+idx+'\').classList.toggle(\'open\')">';
    h+='<div class="pump-icon">🔥</div>';
    h+='<div class="pump-info"><div class="pump-name">$'+c.symbol+' <span class="pump-age">'+c.age_display+'</span> <span class="pump-src">'+c.source+'</span></div>';
    h+='<div class="pump-meta">'+c.price_formatted+' · MC: '+c.mc_formatted+' · Liq: '+c.liquidity_formatted+' · Buys 5m: '+c.buys_5m+'/'+c.sells_5m+'S · Smart: '+c.smart_money_count+'</div></div>';
    h+='<div class="pump-grade" style="background:'+c.grade_color+'20;color:'+c.grade_color+';border:1px solid '+c.grade_color+'40">'+c.score+'/100<br><span style="font-size:8px;letter-spacing:1px">'+c.grade+'</span><br><span style="font-size:9px;color:var(--text3)">'+c.probability+'</span></div>';
    h+='<div class="pump-change '+changeCls+'">'+changeSign+c.change_1h+'%</div><div class="pump-toggle">▼</div></div>';
    h+='<div class="pump-body"><div class="whales-title">📡 SIGNALS DETECTED ('+c.signals.length+')</div>';
    h+='<div class="signals-list">'+sigHtml+'</div>';
    h+='<div class="cc-actions"><a href="'+c.dex_url+'" target="_blank" class="cc-action ape">🦍 APE NOW</a>';
    h+='<a href="https://etherscan.io/token/'+c.address+'" target="_blank" class="cc-action">Ξ ETHERSCAN</a>';
    h+='<a href="https://dexscreener.com/ethereum/'+c.address+'" target="_blank" class="cc-action">📊 DEX</a>';
    h+='<a href="https://gopluslabs.io/token-security/1/'+c.address+'" target="_blank" class="cc-action">🛡️ GOPLUS</a></div></div></div>';
  });
  document.getElementById('prepumpList').innerHTML=h;
}

document.addEventListener('click',function(e){
  if(!e.target||!e.target.classList) return;
  if(e.target.classList.contains('fbtn')){
    document.querySelectorAll('.fbtn').forEach(b=>b.classList.remove('active'));
    e.target.classList.add('active');
    currentFilter=e.target.dataset.filter;
    renderCoins();
  }
  if(e.target.classList.contains('tab')){
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    e.target.classList.add('active');
    document.getElementById('tab-'+e.target.dataset.tab).classList.add('active');
    if(e.target.dataset.tab==='whales'&&allProfiles.length===0) loadProfiles();
    if(e.target.dataset.tab==='prepump'&&allPumps.length===0) loadPrepump();
  }
});

loadCoins();
setInterval(()=>loadCoins(false),180000);
setInterval(()=>{if(document.getElementById('tab-prepump').classList.contains('active'))loadPrepump(false)},90000);
</script>

</body>
</html>
