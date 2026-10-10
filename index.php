<?php
session_start();
require_once __DIR__ . '/config.php';
$user = $_SESSION['user'] ?? null;
$flowers = [];
$market = false;
$db_error = '';
try {
    $pdo = db();
    $flowers = $pdo->query("SELECT * FROM flowers WHERE active=1 ORDER BY display_name")->fetchAll();
    $market = (bool)$pdo->query("SELECT is_open FROM market_status WHERE id=1")->fetchColumn();
} catch (Throwable $e) {
    $db_error = 'Database connection failed. Make sure MySQL is running on port 3308 and the flower_exchange database is imported.';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>FloraX — Digital Flower Exchange</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="assets/florax-logo.svg"><link rel="stylesheet" href="assets/style.css?v=florax-bloom-20261010"><script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<?php if (!$user): ?>
<div class="auth-page"><div class="petal-layer" aria-hidden="true"></div>
  <div class="auth-visual">
    <div class="auth-brand"><img src="assets/florax-logo.svg" alt="FloraX" onerror="this.onerror=null;this.src='assets/florax-logo-fallback.svg'"><span>FloraX</span><small>Digital Flower Exchange</small></div>
    <div class="auth-hero">
      <span class="eyebrow light">SRI LANKA • FLORICULTURE TRADE</span>
      <h1>Trade fresh flowers with confidence.</h1>
      <p>One professional workspace for growers, buyers, supply, market lots, orders and matched trades.</p>
    </div>
    <div class="auth-points"><div><b>For growers</b><span>Publish supply, manage stock and sell.</span></div><div><b>For buyers</b><span>Discover supply and place buy orders.</span></div><div><b>Exchange</b><span>Transparent lots, prices and matching.</span></div><div><b>Control</b><span>Track orders, trades and notifications.</span></div></div>
  </div>
  <div class="auth-panel">
    <div class="auth-top"><img src="assets/florax-logo.svg" alt="FloraX" onerror="this.onerror=null;this.src='assets/florax-logo-fallback.svg'"><div><b>FloraX</b><small>Flower Exchange</small></div></div>
    <div class="auth-tabs"><button class="auth-tab active" data-pane="login">Sign in</button><button class="auth-tab" data-pane="register">Create account</button></div><?php if ($db_error): ?><div class="form-message bad" style="margin-top:14px"><?=htmlspecialchars($db_error)?></div><?php endif; ?>
    <div id="loginPane" class="auth-pane active">
      <h2>Welcome back</h2><p class="muted">Choose your trading role before signing in.</p>
      <div class="role-switch"><button class="role-btn active" data-role="buyer"><strong>Buyer</strong><span>Purchase flowers</span></button><button class="role-btn" data-role="seller"><strong>Seller / Grower</strong><span>Sell your supply</span></button></div>
      <form id="loginForm" class="form-stack"><input type="hidden" name="role" id="loginRole" value="buyer"><label>Email<input type="email" name="email" required autocomplete="email"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="btn primary wide" type="submit">Sign in as Buyer</button><div id="loginMsg" class="form-message"></div></form>
      <div class="demo-box"><div><b>Presentation demo accounts</b><span>Use these to test both sides of the exchange.</span></div><div class="demo-actions"><button onclick="fillDemo('buyer')">Demo Buyer</button><button onclick="fillDemo('seller')">Demo Seller</button></div></div>
    </div>
    <div id="registerPane" class="auth-pane">
      <h2>Create your account</h2><p class="muted">Register as a buyer or a flower grower.</p>
      <form id="registerForm" class="form-stack"><div class="role-switch"><label class="role-radio"><input type="radio" name="role" value="buyer" checked><span><b>Buyer</b><small>Buy flowers</small></span></label><label class="role-radio"><input type="radio" name="role" value="seller"><span><b>Seller / Grower</b><small>Sell flowers</small></span></label></div><div class="two"><label>Full name<input name="full_name" required></label><label>Business / Farm<input name="business_name" required></label></div><div class="two"><label>Phone<input name="phone" required></label><label>Email<input type="email" name="email" required></label></div><div class="two"><label>District<select name="district" required><option value="">Select district</option><?php foreach(['Colombo','Gampaha','Kalutara','Kandy','Matale','Nuwara Eliya','Galle','Matara','Hambantota','Jaffna','Kurunegala','Puttalam','Anuradhapura','Polonnaruwa','Badulla','Monaragala','Ratnapura','Kegalle','Other'] as $d): ?><option><?=htmlspecialchars($d)?></option><?php endforeach; ?></select></label><label>City / Address<input name="address" required></label></div><div class="two"><label>Password<input type="password" name="password" minlength="8" required></label><label>Confirm password<input type="password" name="password_confirm" minlength="8" required></label></div><button class="btn primary wide" type="submit">Create account</button><div id="registerMsg" class="form-message"></div></form>
    </div>
    <p class="auth-footer">FloraX is a university project prototype for digital floriculture trading.</p>
  </div>
</div>

<?php else: ?>
<div class="app-shell">
<header class="topbar"><div class="topbar-inner">
  <button class="mobile-toggle" onclick="toggleSidebar()" aria-label="Open navigation"><i data-lucide="menu"></i></button>
  <a class="brand" href="#" onclick="showPage('dashboard');return false"><img src="assets/florax-logo.svg" alt="FloraX" onerror="this.onerror=null;this.src='assets/florax-logo-fallback.svg'"></a>
  <nav class="top-nav" aria-label="Primary navigation"><button class="active" data-page="dashboard">Home</button><button data-page="market">Market</button><button data-page="market">Flowers</button><button data-page="help">How it works</button><button data-page="profile">Growers</button><button data-page="help">About</button></nav>
  <div class="global-search"><span>⌕</span><input id="globalSearch" placeholder="Search flowers, growers, orders..." onkeydown="if(event.key==='Enter')globalSearch()"><button onclick="globalSearch()">Search</button></div>
  <div class="top-actions"><button class="theme-toggle" id="themeToggle" type="button" onclick="toggleTheme()" aria-label="Toggle light and dark theme">☾ <span>Theme</span></button><button class="icon-btn" onclick="showPage('notifications')" aria-label="Notifications"><i data-lucide="bell"></i><em id="notifCount">0</em></button><button class="profile-mini" onclick="showPage('profile')" aria-label="Open profile"><span class="avatar" id="avatar">A</span><span><b><?=htmlspecialchars($user['full_name'])?></b><small><?=htmlspecialchars(ucfirst($user['role']))?> · <?=htmlspecialchars($user['trader_id'])?></small></span></button><button class="top-logout" onclick="logout()" title="Sign out">Sign out</button></div>
</div></header>
<div class="layout">
<aside class="sidebar" id="sidebar"><div class="side-role"><span class="live-dot"></span><div><b id="marketStatus">CLOSED</b><small id="marketSchedule">Weekdays · 08:00–17:00</small></div><button id="marketAction" class="tiny-btn">Override</button></div>
<nav class="side-nav"><span>WORKSPACE</span><button class="active" data-page="dashboard">⌂ <b>Dashboard</b></button><button data-page="market"><i data-lucide="flower-2"></i><b>Flower Market</b></button><button data-page="orders"><i data-lucide="shopping-bag"></i><b>My Orders</b></button><button data-page="book"><i data-lucide="book-open"></i><b>Order Book</b></button><button data-page="trades"><i data-lucide="arrow-left-right"></i><b>Matched Trades</b></button><button data-page="notifications"><i data-lucide="bell"></i><b>Notifications</b><em id="sideNotif">0</em></button><button data-page="profile"><i data-lucide="user-round"></i><b>My Profile</b></button><button data-page="help"><i data-lucide="circle-help"></i><b>How it works</b></button></nav>
<div class="side-bottom"><div class="secure"><b>Protected trading</b><span>Role-based access · hashed passwords · server validation</span></div><button class="logout" onclick="logout()"><i data-lucide="log-out"></i> Sign out</button></div></aside>
<main class="content">
<section id="dashboard" class="page active">
  <div class="page-title"><div><span class="eyebrow">CONTROL CENTER</span><h1>Good to see you, <?=htmlspecialchars(strtok($user['full_name'],' '))?>.</h1><p>Manage your flower trading activity from one place.</p></div><div class="title-actions"><span class="role-pill <?=htmlspecialchars($user['role'])?>"><?=htmlspecialchars(strtoupper($user['role']))?> · <?=htmlspecialchars($user['trader_id'])?></span><button class="btn secondary" onclick="logout()">Switch account</button><button class="btn primary" onclick="openOrderModal()">+ New Order</button></div></div>
  <div class="hero"><div class="hero-copy"><span class="eyebrow light">SRI LANKA • FLOWER EXCHANGE</span><h2>Where every bloom finds its market.</h2><p>Connect with growers and buyers, discover fresh flower lots, compare live reference prices and confirm trades in one trusted marketplace.</p><div class="hero-buttons"><button class="btn cream" onclick="showPage('market')">Explore Flower Market →</button><button class="btn ghost-light" onclick="showPage('help')">How it works</button></div></div><div class="hero-photo"></div></div>
  <div class="category-strip"><button class="category-card" onclick="showPage('market');document.getElementById('flowerSearch').value='rose';renderMarket()"><img src="https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=220&q=85"><span><b>Roses</b>View Market →</span></button><button class="category-card" onclick="showPage('market');document.getElementById('flowerSearch').value='lily';renderMarket()"><img src="https://images.unsplash.com/photo-1522383225653-ed111181a951?auto=format&fit=crop&w=220&q=85"><span><b>Lilies</b>View Market →</span></button><button class="category-card" onclick="showPage('market');document.getElementById('flowerSearch').value='gerbera';renderMarket()"><img src="https://images.unsplash.com/photo-1495231916356-a86217efff12?auto=format&fit=crop&w=220&q=85"><span><b>Gerberas</b>View Market →</span></button><button class="category-card" onclick="showPage('market');document.getElementById('flowerSearch').value='tulip';renderMarket()"><img src="https://images.unsplash.com/photo-1523659591886-4e6cf4f429b7?auto=format&fit=crop&w=220&q=85"><span><b>Tulips</b>View Market →</span></button><button class="category-card" onclick="showPage('market')"><img src="https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=220&q=85"><span><b>Fresh Lots</b>Browse supply →</span></button><button class="category-card" onclick="showPage('book')"><span style="font-size:30px">🌿</span><span><b>All Flowers</b>Browse All →</span></button></div>
  <div class="stat-grid"><article><span>Market</span><b id="statMarket">Closed</b><small>Current session</small></article><article><span>My open orders</span><b id="statMyOrders">0</b><small>Waiting in the book</small></article><article><span>Matched trades</span><b id="statTrades">0</b><small>Recent executions</small></article><article><span>Unread alerts</span><b id="statAlerts">0</b><small>Exchange activity</small></article></div>
  <section class="panel market-board"><div class="panel-head"><div><span class="eyebrow">LIVE EXCHANGE</span><h3>Market prices</h3><p class="muted">Last traded price, today’s change and session range.</p></div><span class="market-live-pill"><span class="live-dot"></span> LIVE</span></div><div id="priceBoard" class="price-board"></div></section><div class="grid-2" style="margin-top:18px"><section class="panel"><div class="panel-head"><div><span class="eyebrow">FEATURED FLOWERS</span><h3>Fresh supply from growers</h3></div><button class="link-btn" onclick="showPage('market')">View all flowers →</button></div><div id="dashboardFlowers" class="mini-products"></div></section><section class="panel"><div class="panel-head"><div><span class="eyebrow">MARKET SNAPSHOT</span><h3>Reference prices</h3></div><span class="role-pill">UPDATED LIVE</span></div><div id="marketSnapshot" class="activity-list"></div></section></div>
  <div class="grid-2" style="margin-top:18px"><section class="panel"><div class="panel-head"><div><span class="eyebrow">RECENT ACTIVITY</span><h3>What's happening</h3></div></div><div id="dashboardActivity" class="activity-list"></div></section><section class="panel"><div class="panel-head"><div><span class="eyebrow">TRADING DESK</span><h3>Ready to trade?</h3></div></div><p class="muted" style="line-height:1.7">Buyers can place demand and growers can offer supply. Compatible orders are matched automatically when the market is open.</p><button class="btn primary" onclick="openOrderModal()">Place a market order →</button></section></div>
</section>
<section id="market" class="page"><div class="page-title"><div><span class="eyebrow">EXPLORER</span><h1>Flower Market</h1><p>Browse current flower lots, live prices and available supply.</p></div><button class="btn primary" onclick="openOrderModal()">+ Place order</button></div><div class="market-toolbar"><input id="flowerSearch" placeholder="Search rose, tulip, gerbera..." oninput="renderMarket()"><select id="flowerSort" onchange="renderMarket()"><option value="featured">Featured</option><option value="price_low">Price low → high</option><option value="price_high">Price high → low</option><option value="name">Name A → Z</option></select><button class="filter-chip active">All flowers</button></div><section class="panel market-table-panel"><div class="panel-head"><div><span class="eyebrow">PRICE TICKER</span><h3>Today’s market</h3></div><span class="muted">Updates after every matched trade</span></div><div id="marketTicker" class="market-ticker"></div></section><div id="flowerGrid" class="product-grid"></div></section>
<section id="orders" class="page"><div class="page-title"><div><span class="eyebrow">TRADE MANAGEMENT</span><h1>My Orders</h1><p>Track your active and completed BUY / SELL orders.</p></div><button class="btn primary" onclick="openOrderModal()">+ New order</button></div><div class="summary-strip"><span>Open <b id="ordersOpen">0</b></span><span>Filled <b id="ordersFilled">0</b></span><span>Cancelled <b id="ordersCancelled">0</b></span></div><div id="myOrders" class="table-card"></div></section>
<section id="book" class="page"><div class="page-title"><div><span class="eyebrow">LIVE MARKET</span><h1>Order Book</h1><p>Visible demand and supply across the exchange.</p></div><button class="btn primary" onclick="openOrderModal()">+ New order</button></div><div class="market-note"><span class="live-dot"></span><b id="bookMarketStatus">Market closed</b><span>Orders are matched automatically by price and time priority.</span></div><div class="book-grid"><section class="book-panel"><div class="book-head buy"><b>BUY ORDERS</b><span>Highest price first</span></div><div id="buyBook"></div></section><section class="book-panel"><div class="book-head sell"><b>SELL ORDERS</b><span>Lowest price first</span></div><div id="sellBook"></div></section></div></section>
<section id="trades" class="page"><div class="page-title"><div><span class="eyebrow">EXECUTION HISTORY</span><h1>Matched Trades</h1><p>Follow executions and confirm your side of a completed match.</p></div></div><div id="tradesList" class="trade-list"></div></section>
<section id="notifications" class="page"><div class="page-title"><div><span class="eyebrow">INBOX</span><h1>Notifications</h1><p>Order acceptance, matches, confirmations and account alerts.</p></div><button class="btn secondary" onclick="markNotifications()">Mark all read</button></div><div id="notificationsList" class="notification-list"></div></section>
<section id="profile" class="page"><div class="page-title"><div><span class="eyebrow">ACCOUNT</span><h1>My Profile</h1><p>Keep your trader and business details up to date.</p></div></div><div class="profile-grid"><section class="panel profile-card"><div class="profile-cover"></div><div class="profile-main"><div class="big-avatar" id="bigAvatar">A</div><h2><?=htmlspecialchars($user['full_name'])?></h2><span class="role-pill <?=htmlspecialchars($user['role'])?>"><?=htmlspecialchars(ucfirst($user['role']))?> · <?=htmlspecialchars($user['trader_id'])?></span><p><?=htmlspecialchars($user['business_name'])?></p></div></section><section class="panel"><div class="panel-head"><div><span class="eyebrow">TRADER DETAILS</span><h3>Edit profile</h3></div></div><form id="profileForm" class="form-stack"><div class="two"><label>Full name<input name="full_name" value="<?=htmlspecialchars($user['full_name'])?>" required></label><label>Business / Farm<input name="business_name" value="<?=htmlspecialchars($user['business_name'])?>" required></label></div><div class="two"><label>Phone<input name="phone" value="<?=htmlspecialchars($user['phone'])?>" required></label><label>District<input name="district" value="<?=htmlspecialchars($user['district'])?>" required></label></div><label>Address<input name="address" value="<?=htmlspecialchars($user['address'])?>" required></label><button class="btn primary" type="submit">Save changes</button><div id="profileMsg" class="form-message"></div></form></section></div></section>
<section id="help" class="page"><div class="page-title"><div><span class="eyebrow">GUIDE</span><h1>How FloraX works</h1><p>A simple exchange flow inspired by professional floriculture platforms.</p></div></div><div class="how-grid"><article><span>01</span><h3>Growers publish supply</h3><p>Seller accounts can offer flower lots with price and minimum lot rules.</p></article><article><span>02</span><h3>Buyers discover flowers</h3><p>Use the catalogue and market filters to review available flowers and reference prices.</p></article><article><span>03</span><h3>Place a market order</h3><p>Orders are validated by role, lot size and market status before entering the book.</p></article><article><span>04</span><h3>Automatic matching</h3><p>Compatible BUY and SELL orders are matched by price and time priority.</p></article><article><span>05</span><h3>Confirm the trade</h3><p>Both sides can confirm the matched execution and keep a clear audit trail.</p></article><article><span>06</span><h3>Track everything</h3><p>Orders, trades, notifications and activity remain visible in the workspace.</p></article></div></section>
</main></div></div>
<div id="modal" class="modal"><div class="modal-card"><button class="modal-close" onclick="closeModal()">×</button><span class="eyebrow">ORDER ENTRY</span><h2>Place a market order</h2><p class="muted">Your role controls whether you can place a BUY or SELL order.</p><form id="orderForm" class="form-stack"><div class="two"><label>Flower<select name="symbol" id="orderSymbol" required></select></label><label>Order side<select name="side" id="orderSide" required></select></label></div><div class="two"><label>Quantity<input name="qty" id="orderQty" type="number" min="1" required></label><label>Price per unit<input name="price" id="orderPrice" type="number" min="0.01" step="0.01" required></label></div><div class="order-total"><span>Estimated order value</span><b id="orderTotal">Rs. 0.00</b></div><button class="btn primary wide" type="submit">Submit order</button><div id="orderMsg" class="form-message"></div></form></div></div>
<div id="toastArea"></div>
<script>window.FLORAX={role:<?=json_encode($user['role'])?>,trader:<?=json_encode($user['trader_id'])?>,flowers:<?=json_encode($flowers)?>,market:<?=$market?'true':'false'?>};</script>

<?php endif; ?>
<script>
(function(){
  const layer=document.querySelector('.petal-layer');
  if(!layer || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  for(let i=0;i<18;i++){
    const petal=document.createElement('span'); petal.className='floating-petal';
    petal.style.setProperty('--left',(Math.random()*100)+'%');
    petal.style.setProperty('--duration',(11+Math.random()*13)+'s');
    petal.style.setProperty('--delay',(-Math.random()*22)+'s');
    petal.style.setProperty('--rotate',(Math.random()*160)+'deg');
    layer.appendChild(petal);
  }
})();
</script>
<script src="assets/app.js?v=florax-icons-20261010"></script>
</body></html>
