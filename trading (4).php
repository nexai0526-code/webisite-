<?php
session_start();
$ADMIN_PASS = 'CallGod2026!';
if (!isset($_SESSION['admin_auth'])) {
    if (isset($_POST['password']) && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin_auth'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html><head><title>Login</title>
        <style>
        body{background:#08080F;color:#F0F0F8;font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
        .box{background:#12121E;border:1px solid rgba(255,107,107,.2);border-radius:14px;padding:40px;width:340px}
        h2{font-family:'Syne',sans-serif;margin-bottom:24px;background:linear-gradient(135deg,#FF6B6B,#FFD32A,#00E676,#00D2FF,#7C4DFF);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        input{width:100%;padding:14px;background:#0B0B13;border:1px solid rgba(255,255,255,.08);border-radius:8px;color:#fff;font-size:14px;margin-bottom:14px}
        button{width:100%;padding:14px;background:linear-gradient(135deg,#FF6B6B,#FFD32A,#00E676,#00D2FF,#7C4DFF);border:none;color:#fff;font-weight:600;border-radius:8px;cursor:pointer}
        </style></head><body>
        <form class="box" method="POST"><h2>🐋 Trading Panel</h2><input type="password" name="password" placeholder="Admin Password" autofocus><button>Enter</button></form>
        </body></html>
        <?php exit;
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
--border:rgba(255,255,255,.05);--eth:#627EEA;--green:#00E676;--red:#FF4757;--amber:#FFD32A;--purple:#7C4DFF;--orange:#FF9F43;--cyan:#00D2FF;
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
.live-pulse{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;background:rgba(0,230,118,.1);border:1px solid rgba(0,230,118,.3);border-radius:6px;color:var(--green);font-family:monospace;font-size:10px;letter-spacing:1px}
.live-pulse::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--green);animation:pulse 1.5s infinite}

.tabs{display:flex;gap:6px;background:var(--card);border:1px solid var(--border);border-radius:14px;padding:8px;margin-bottom:18px;overflow-x:auto}
.tab{flex:1;min-width:120px;padding:12px 16px;background:transparent;border:1px solid transparent;border-radius:10px;color:var(--text2);font-family:'Syne',sans-serif;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap;text-align:center}
.tab:hover{color:var(--text)}
.tab.active{background:rgba(98,126,234,.1);border-color:rgba(98,126,234,.3);color:var(--eth)}
.tab[data-tab="feed"].active{background:rgba(0,230,118,.1);border-color:rgba(0,230,118,.3);color:var(--green)}
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
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(1.2)}}
.refresh-btn{background:rgba(98,126,234,.1);border:1px solid rgba(98,126,234,.2);color:var(--eth);padding:6px 12px;border-radius:6px;font-size:10px;cursor:pointer;font-family:'JetBrains Mono',monospace;letter-spacing:1px}

.filters{padding:12px 18px;background:rgba(0,0,0,.2);border-bottom:1px solid var(--border);display:flex;gap:6px;flex-wrap:wrap}
.fbtn{padding:6px 12px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;color:var(--text2);font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:1px;cursor:pointer;transition:all .2s}
.fbtn:hover{border-color:var(--eth);color:var(--eth)}
.fbtn.active{background:rgba(0,230,118,.15);border-color:var(--green);color:var(--green)}

/* Live Feed styles */
.feed-row{display:grid;grid-template-columns:auto 1fr auto auto auto;gap:14px;align-items:center;padding:14px 22px;border-bottom:1px solid rgba(255,255,255,.03);transition:background .2s;text-decoration:none;color:var(--text);position:relative}
.feed-row:hover{background:rgba(255,255,255,.02)}
.feed-row.buy{border-left:3px solid var(--green)}
.feed-row.sell{border-left:3px solid var(--red)}
.feed-row.big-trade::before{content:'💎';position:absolute;top:6px;left:-2px;font-size:11px}
.feed-row.pumping::after{content:'🔥';position:absolute;top:6px;right:140px;font-size:11px}
.feed-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;flex-shrink:0}
.feed-icon.buy{background:rgba(0,230,118,.15);color:var(--green)}
.feed-icon.sell{background:rgba(255,71,87,.15);color:var(--red)}
.feed-info{min-width:0}
.feed-title{font-family:'Syne',sans-serif;font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.feed-whale{font-family:'JetBrains Mono',monospace;font-size:10px;padding:2px 8px;border-radius:4px;letter-spacing:1px;text-transform:uppercase}
.feed-whale.exchange{background:rgba(255,211,42,.1);color:var(--amber)}
.feed-whale.mev{background:rgba(255,71,87,.1);color:var(--red)}
.feed-whale.trader{background:rgba(0,210,255,.1);color:var(--cyan)}
.feed-whale.whale{background:rgba(255,107,203,.1);color:#FF6BCB}
.feed-whale.vc{background:rgba(124,77,255,.1);color:var(--purple)}
.feed-whale.founder{background:rgba(0,230,118,.1);color:var(--green)}
.feed-whale.memecoin{background:rgba(255,159,67,.1);color:var(--orange)}
.feed-meta{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);margin-top:4px}
.feed-action{font-family:'Syne',sans-serif;font-weight:700;font-size:11px;padding:5px 10px;border-radius:6px;letter-spacing:1px}
.feed-action.buy{background:rgba(0,230,118,.15);color:var(--green)}
.feed-action.sell{background:rgba(255,71,87,.15);color:var(--red)}
.feed-amount{text-align:right;font-family:'JetBrains Mono',monospace}
.feed-amount-v{font-size:14px;font-weight:700}
.feed-amount-v.buy{color:var(--green)}
.feed-amount-v.sell{color:var(--red)}
.feed-amount-l{font-size:9px;color:var(--text3);margin-top:2px}
.feed-time{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text3);text-align:right;min-width:50px}

/* Other shared styles for coin/profile cards */
.coin-card,.profile-card,.pump-card{border-bottom:1px solid rgba(255,255,255,.03);transition:background .2s}
.coin-card:hover{background:rgba(255,255,255,.015)}
.profile-card:hover{background:rgba(124,77,255,.02)}
.pump-card:hover{background:rgba(255,71,87,.03)}
.cc-head,.pc-head,.pump-head{padding:14px 22px;display:grid;gap:14px;align-items:center;cursor:pointer}
.cc-head,.pump-head{grid-template-columns:auto 1fr auto auto auto}
.pc-head{grid-template-columns:auto 1fr auto auto auto}
.cc-icon,.pump-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:18px;font-weight:700}
.cc-icon{background:linear-gradient(135deg,rgba(98,126,234,.15),rgba(98,126,234,.05));color:var(--eth)}
.pump-icon{background:linear-gradient(135deg,rgba(255,71,87,.15),rgba(255,159,67,.05));color:var(--red)}
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
  .tab{padding:10px 10px;font-size:10px;min-width:0}
  .stats{grid-template-columns:1fr 1fr;gap:8px}
  .s-card{padding:12px}
  .s-val{font-size:18px}
  .feed-row{grid-template-columns:auto 1fr auto;gap:10px;padding:12px 14px}
  .feed-amount,.feed-time{display:none}
  .feed-title{font-size:12px}
  .feed-meta{font-size:9px}
  .cc-head,.pc-head,.pump-head{grid-template-columns:auto 1fr auto;padding:12px 14px;gap:10px}
  .cc-change,.pc-portfolio,.pump-change{display:none}
  .cc-name,.pc-name,.pump-name{font-size:13px}
  .cc-meta,.pc-meta,.pump-meta{font-size:10px}
  .cc-safety,.pump-grade{font-size:10px;padding:5px 8px;min-width:80px}
  .pc-winrate{font-size:12px;padding:6px 8px;min-width:55px}
  .cc-body,.pc-body,.pump-body{padding:0 14px 14px}
  .safety-grid{grid-template-columns:1fr 1fr;gap:6px}
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
    <h1><div class="logo-icon">Ξ</div>ETH Whale Intelligence <span class="live-pulse">LIVE</span></h1>
    <div class="header-links">
      <a href="/whale.html" target="_blank">🐋 Public</a>
      <a href="/admin.php">🔮 Oracle</a>
      <a href="/">CallGod</a>
      <form method="POST" style="display:inline"><input type="hidden" name="action" value="logout"><button class="logout">Logout</button></form>
    </div>
  </div>

  <div class="tabs">
    <button class="tab active" data-tab="feed">🔔 Live Whale Feed</button>
    <button class="tab" data-tab="prepump">🔥 Pre-Pump</button>
    <button class="tab" data-tab="coins">🪙 New Coins</button>
    <button class="tab" data-tab="whales">🐋 Smart Money</button>
  </div>

  <!-- TAB: LIVE FEED -->
  <div class="tab-content active" id="tab-feed">
    <div class="stats">
      <div class="s-card c2"><div class="s-lab">TOTAL WHALES</div><div class="s-val green" id="feedWhales">—</div><div class="s-sub">Tracked</div></div>
      <div class="s-card c1"><div class="s-lab">ALERTS (24H)</div><div class="s-val eth" id="feedAlerts">—</div><div class="s-sub">All actions</div></div>
      <div class="s-card c3"><div class="s-lab">💎 BIG TRADES</div><div class="s-val amber" id="feedBig">—</div><div class="s-sub">&gt;$10K</div></div>
      <div class="s-card c6"><div class="s-lab">🔥 PUMPING</div><div class="s-val orange" id="feedPumping">—</div><div class="s-sub">+10% / 1h</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot" style="background:var(--green)"></div><span>🔔 Live Whale Feed — All 103 Whales Tracked</span></div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <span id="feedMeta" style="font-size:10px;color:var(--text3);font-family:monospace"></span>
          <button class="refresh-btn" onclick="loadFeed(true)" style="background:rgba(0,230,118,.1);border-color:rgba(0,230,118,.2);color:var(--green)">🔄 SCAN NEXT</button>
        </div>
      </div>
      <div class="filters">
        <button class="fbtn active" data-feed-filter="all">ALL</button>
        <button class="fbtn" data-feed-filter="buy">🟢 BUYS</button>
        <button class="fbtn" data-feed-filter="sell">🔴 SELLS</button>
        <button class="fbtn" data-feed-filter="big">💎 BIG TRADES</button>
        <button class="fbtn" data-feed-filter="pumping">🔥 PUMPING</button>
      </div>
      <div id="feedList"><div class="empty">Loading whale activity feed...</div></div>
    </div>
  </div>

  <!-- TAB: PRE-PUMP -->
  <div class="tab-content" id="tab-prepump">
    <div class="stats">
      <div class="s-card c4"><div class="s-lab">🔥 PUMP IMMINENT</div><div class="s-val red" id="ppImminent">—</div><div class="s-sub">90%+ prob</div></div>
      <div class="s-card c6"><div class="s-lab">🟠 HIGH ALERT</div><div class="s-val orange" id="ppHigh">—</div><div class="s-sub">70-85%</div></div>
      <div class="s-card c3"><div class="s-lab">🟡 STRONG</div><div class="s-val amber" id="ppStrong">—</div><div class="s-sub">55-70%</div></div>
      <div class="s-card c1"><div class="s-lab">🔵 EARLY</div><div class="s-val eth" id="ppEarly">—</div><div class="s-sub">40-55%</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot" style="background:var(--red)"></div><span>🔥 Pre-Pump Detector</span></div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <span id="ppMeta" style="font-size:10px;color:var(--text3);font-family:monospace"></span>
          <button class="refresh-btn" onclick="loadPrepump(true)" style="background:rgba(255,71,87,.1);border-color:rgba(255,71,87,.2);color:var(--red)">🔄 REFRESH</button>
        </div>
      </div>
      <div id="prepumpList"><div class="empty">Click tab to load.</div></div>
    </div>
  </div>

  <!-- TAB: NEW COINS -->
  <div class="tab-content" id="tab-coins">
    <div class="stats">
      <div class="s-card c1"><div class="s-lab">NEW ETH COINS</div><div class="s-val eth" id="statCoins">—</div><div class="s-sub">&lt; 72h old</div></div>
      <div class="s-card c2"><div class="s-lab">🛡️ SAFE</div><div class="s-val green" id="statSafe">—</div><div class="s-sub">9+/10</div></div>
      <div class="s-card c3"><div class="s-lab">🐋 WHALES</div><div class="s-val amber" id="statWhales">—</div><div class="s-sub">$1K+</div></div>
      <div class="s-card c4"><div class="s-lab">⚠️ RISKY</div><div class="s-val red" id="statRisky">—</div><div class="s-sub">Avoid</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot"></div><span>Ξ New ETH Coins · Safety · Whales</span></div>
        <button class="refresh-btn" onclick="loadCoins(true)">🔄 REFRESH</button>
      </div>
      <div class="filters">
        <button class="fbtn active" data-filter="all">ALL</button>
        <button class="fbtn" data-filter="SAFE">🟢 SAFE</button>
        <button class="fbtn" data-filter="OK">🟡 OK</button>
        <button class="fbtn" data-filter="RISKY">🟠 RISKY</button>
        <button class="fbtn" data-filter="DANGER">🔴 DANGER</button>
      </div>
      <div id="coinsList"><div class="empty">Click tab to load.</div></div>
    </div>
  </div>

  <!-- TAB: SMART MONEY -->
  <div class="tab-content" id="tab-whales">
    <div class="stats">
      <div class="s-card c5"><div class="s-lab">SMART WALLETS</div><div class="s-val purple" id="profWallets">—</div><div class="s-sub">Tracked</div></div>
      <div class="s-card c2"><div class="s-lab">TOTAL HELD</div><div class="s-val green" id="profPortfolio">—</div><div class="s-sub">Combined</div></div>
      <div class="s-card c1"><div class="s-lab">COINS TRACKED</div><div class="s-val eth" id="profCoins">—</div><div class="s-sub">7 days</div></div>
      <div class="s-card c3"><div class="s-lab">AVG WIN RATE</div><div class="s-val amber" id="profWinRate">—</div><div class="s-sub">All</div></div>
    </div>
    <div class="panel">
      <div class="p-head">
        <div class="p-title"><div class="dot" style="background:var(--purple)"></div><span>🐋 Smart Money Profiles</span></div>
        <button class="refresh-btn" onclick="loadProfiles(true)" style="background:rgba(124,77,255,.1);border-color:rgba(124,77,255,.2);color:var(--purple)">🔄 REFRESH</button>
      </div>
      <div id="profilesList"><div class="empty">Click tab to load.</div></div>
    </div>
  </div>
</div>

<script>
let allCoins=[],allProfiles=[],allPumps=[],allFeed=[];
let currentFilter='all',feedFilter='all';
let nextBatch=0;

async function loadFeed(force){
  try{
    const url='/whale_feed.php?batch='+nextBatch+(force?'&t='+Date.now():'');
    const r=await fetch(url);
    const d=await r.json();
    if(!d.success) return;
    nextBatch=d.next_batch||0;
    const s=d.stats;
    document.getElementById('feedWhales').textContent=s.total_whales||0;
    document.getElementById('feedAlerts').textContent=s.total_alerts||0;
    document.getElementById('feedBig').textContent=s.big_trades||0;
    document.getElementById('feedPumping').textContent=s.pumping||0;
    document.getElementById('feedMeta').textContent='Updated: '+d.updated_at+' · Next batch: '+nextBatch+'/'+Math.ceil(s.total_whales/25);
    allFeed=d.alerts||[];
    renderFeed();
  }catch(e){console.error(e)}
}

function renderFeed(){
  let alerts=allFeed;
  if(feedFilter==='buy') alerts=alerts.filter(a=>a.action==='buy');
  else if(feedFilter==='sell') alerts=alerts.filter(a=>a.action==='sell');
  else if(feedFilter==='big') alerts=alerts.filter(a=>a.is_big_trade);
  else if(feedFilter==='pumping') alerts=alerts.filter(a=>a.is_pumping);
  if(!alerts||alerts.length===0){document.getElementById('feedList').innerHTML='<div class="empty">No alerts match filter. Click SCAN NEXT to fetch more.</div>';return}
  let h='';
  alerts.forEach(a=>{
    const classes='feed-row '+a.action+(a.is_big_trade?' big-trade':'')+(a.is_pumping?' pumping':'');
    const icon=a.action==='buy'?'↑':'↓';
    const changeStr=a.change_1h!==0?(a.change_1h>=0?'+':'')+a.change_1h+'%':'—';
    const changeCls=a.change_1h>=0?'buy':'sell';
    h+='<a href="'+a.explorer_url+'" target="_blank" class="'+classes+'">';
    h+='<div class="feed-icon '+a.action+'">'+icon+'</div>';
    h+='<div class="feed-info">';
    h+='<div class="feed-title">$'+a.symbol+' <span class="feed-whale '+a.whale_type+'">'+a.whale_label+'</span></div>';
    h+='<div class="feed-meta">'+a.wallet+' · '+a.amount_formatted+' '+a.symbol+' · MC: '+(a.mc_formatted||'—')+' · 1h: '+changeStr+'</div>';
    h+='</div>';
    h+='<div class="feed-action '+a.action+'">'+a.action.toUpperCase()+'</div>';
    h+='<div class="feed-amount"><div class="feed-amount-v '+a.action+'">'+(a.usd_formatted||'—')+'</div><div class="feed-amount-l">'+(a.action==='buy'?'BOUGHT':'SOLD')+'</div></div>';
    h+='<div class="feed-time">'+a.time_display+'</div>';
    h+='</a>';
  });
  document.getElementById('feedList').innerHTML=h;
}

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
        whalesHtml+='<div class="wr-meta">🟢 '+w.count_buys+' · 🔴 '+w.count_sells+'</div></div>';
        whalesHtml+='<div class="wr-net '+nc+'">'+ns+w.net_usd_formatted+'</div>';
        whalesHtml+='<div class="wr-time">'+w.time_display+'</div></a>';
      });
    } else whalesHtml='<div class="empty" style="padding:20px">No whales yet.</div>';
    h+='<div class="coin-card" id="cc-'+idx+'">';
    h+='<div class="cc-head" onclick="document.getElementById(\'cc-'+idx+'\').classList.toggle(\'open\')">';
    h+='<div class="cc-icon">'+c.symbol.charAt(0)+'</div>';
    h+='<div class="cc-info"><div class="cc-name">$'+c.symbol+' <span class="cc-age">'+c.age_display+'</span></div>';
    h+='<div class="cc-meta">'+c.price_formatted+' · MC: '+c.mc_formatted+' · Liq: '+c.liquidity_formatted+'</div></div>';
    h+='<div class="cc-safety" style="background:'+c.safety_color+'20;color:'+c.safety_color+';border:1px solid '+c.safety_color+'40">'+c.safety_score+'/10<br><span style="font-size:9px">'+c.safety_grade+'</span></div>';
    h+='<div class="cc-change '+changeCls+'">'+changeSign+c.change_1h+'%</div><div class="cc-toggle">▼</div></div>';
    h+='<div class="cc-body"><div class="whales-title">🛡️ SAFETY</div><div class="safety-grid">'+checksHtml+'</div>';
    h+='<div class="whales-title">🐋 WHALES</div>'+whalesHtml;
    h+='<div class="cc-actions"><a href="'+c.dex_url+'" target="_blank" class="cc-action buy">💰 BUY</a>';
    h+='<a href="https://etherscan.io/token/'+c.address+'" target="_blank" class="cc-action">Ξ ETHERSCAN</a></div></div></div>';
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
    allProfiles=d.profiles||[];
    renderProfiles();
  }catch(e){console.error(e)}
}

function renderProfiles(){
  if(!allProfiles||allProfiles.length===0){document.getElementById('profilesList').innerHTML='<div class="empty">No wallets found.</div>';return}
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
      coinsHtml+='<div class="cr-meta">'+c.first_buy_age+' · '+c.count_buys+'B/'+c.count_sells+'S</div></div>';
      coinsHtml+='<div class="cr-amount"><div class="cr-amount-v">'+c.bought_usd_formatted+'</div><div class="cr-amount-l">BOUGHT</div></div>';
      coinsHtml+='<div class="cr-amount"><div class="cr-amount-v" style="color:'+c.status_color+'">'+c.held_usd_formatted+'</div><div class="cr-amount-l">HELD</div></div>';
      coinsHtml+='<div class="cr-change '+changeCls+'">'+changeSign+c.change_24h+'%</div></a>';
    });
    h+='<div class="profile-card" id="pc-'+idx+'">';
    h+='<div class="pc-head" onclick="document.getElementById(\'pc-'+idx+'\').classList.toggle(\'open\')">';
    h+='<div class="pc-icon">🐋</div>';
    h+='<div class="pc-info"><div class="pc-name">'+p.wallet+' <span class="pc-label">'+p.discovered_from+'</span></div>';
    h+='<div class="pc-meta">'+p.coins_count+' coins · '+p.wins+'W/'+p.losses+'L</div></div>';
    h+='<div class="pc-portfolio"><div class="pc-port-val">'+p.total_held_formatted+'</div><div class="pc-port-lab">HELD</div></div>';
    h+='<div class="pc-winrate '+winCls+'">'+p.win_rate+'%<br><span style="font-size:8px">WIN</span></div>';
    h+='<div class="pc-toggle">▼</div></div>';
    h+='<div class="pc-body"><div class="whales-title">📊 LAST 7 DAYS</div><div class="coin-list">'+coinsHtml+'</div></div></div>';
  });
  document.getElementById('profilesList').innerHTML=h;
}

async function loadPrepump(force){
  try{
    document.getElementById('prepumpList').innerHTML='<div class="empty">🔍 Scanning... (~60s)</div>';
    const url='/prepump.php'+(force?'?t='+Date.now():'');
    const r=await fetch(url);
    const d=await r.json();
    if(!d.success) return;
    const s=d.stats;
    document.getElementById('ppImminent').textContent=s.pump_imminent||0;
    document.getElementById('ppHigh').textContent=s.high_alert||0;
    document.getElementById('ppStrong').textContent=s.strong_signal||0;
    document.getElementById('ppEarly').textContent=s.early_signal||0;
    allPumps=d.coins||[];
    renderPrepump();
  }catch(e){console.error(e)}
}

function renderPrepump(){
  if(!allPumps||allPumps.length===0){document.getElementById('prepumpList').innerHTML='<div class="empty">No signals.</div>';return}
  let h='';
  allPumps.forEach((c,idx)=>{
    const changeCls=c.change_1h>=0?'up':'down';
    const changeSign=c.change_1h>=0?'+':'';
    let sigHtml='';
    c.signals.forEach(sig=>{
      const cls=sig.strong?'strong':'';
      sigHtml+='<div class="signal-item '+cls+'"><div class="signal-icon">'+sig.icon+'</div><div>'+sig.text+'</div>';
      if(sig.strong) sigHtml+='<div class="signal-strong-badge">⭐</div>';
      sigHtml+='</div>';
    });
    h+='<div class="pump-card" id="pump-'+idx+'">';
    h+='<div class="pump-head" onclick="document.getElementById(\'pump-'+idx+'\').classList.toggle(\'open\')">';
    h+='<div class="pump-icon">🔥</div>';
    h+='<div class="pump-info"><div class="pump-name">$'+c.symbol+' <span class="pump-age">'+c.age_display+'</span></div>';
    h+='<div class="pump-meta">'+c.price_formatted+' · MC: '+c.mc_formatted+'</div></div>';
    h+='<div class="pump-grade" style="background:'+c.grade_color+'20;color:'+c.grade_color+';border:1px solid '+c.grade_color+'40">'+c.score+'/100<br><span style="font-size:8px">'+c.grade+'</span></div>';
    h+='<div class="pump-change '+changeCls+'">'+changeSign+c.change_1h+'%</div><div class="pump-toggle">▼</div></div>';
    h+='<div class="pump-body"><div class="whales-title">📡 SIGNALS</div><div class="signals-list">'+sigHtml+'</div>';
    h+='<div class="cc-actions"><a href="'+c.dex_url+'" target="_blank" class="cc-action ape">🦍 APE</a>';
    h+='<a href="https://etherscan.io/token/'+c.address+'" target="_blank" class="cc-action">Ξ</a></div></div></div>';
  });
  document.getElementById('prepumpList').innerHTML=h;
}

document.addEventListener('click',function(e){
  if(!e.target||!e.target.classList) return;
  if(e.target.classList.contains('fbtn')){
    const ff=e.target.dataset.feedFilter;
    if(ff){
      document.querySelectorAll('.fbtn[data-feed-filter]').forEach(b=>b.classList.remove('active'));
      e.target.classList.add('active');
      feedFilter=ff;
      renderFeed();
    } else {
      document.querySelectorAll('.fbtn[data-filter]').forEach(b=>b.classList.remove('active'));
      e.target.classList.add('active');
      currentFilter=e.target.dataset.filter;
      renderCoins();
    }
  }
  if(e.target.classList.contains('tab')){
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    e.target.classList.add('active');
    const tab=e.target.dataset.tab;
    document.getElementById('tab-'+tab).classList.add('active');
    if(tab==='whales'&&allProfiles.length===0) loadProfiles();
    if(tab==='prepump'&&allPumps.length===0) loadPrepump();
    if(tab==='coins'&&allCoins.length===0) loadCoins();
  }
});

loadFeed();
setInterval(()=>{if(document.getElementById('tab-feed').classList.contains('active'))loadFeed(false)},60000);
</script>

</body>
</html>
