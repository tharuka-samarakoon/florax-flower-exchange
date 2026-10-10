const F=window.FLORAX||{};let S={market:F.market,flowers:F.flowers||[],my_orders:[],orders:[],trades:[],notifications:[],activity:[],stats:{}};let CSRF='';
const $=id=>document.getElementById(id);
const IMG={rose:'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=1000&q=88',tulip:'https://images.unsplash.com/photo-1523659591886-4e6cf4f429b7?auto=format&fit=crop&w=1000&q=88',gerbera:'https://images.unsplash.com/photo-1495231916356-a86217efff12?auto=format&fit=crop&w=1000&q=88',lily:'https://images.unsplash.com/photo-1522383225653-ed111181a951?auto=format&fit=crop&w=1000&q=88'};
function img(f){return f.image_url||IMG[String(f.symbol).toLowerCase()]||IMG.rose}
async function api(action,options={}){options.headers=options.headers||{};if(options.method==='POST'){if(options.body instanceof FormData)options.body.append('csrf',CSRF);else{options.headers['Content-Type']='application/x-www-form-urlencoded';options.body=new URLSearchParams({...options.body,csrf:CSRF})}}const r=await fetch('api/api.php?action='+encodeURIComponent(action),options);let d;try{d=await r.json()}catch{throw Error('Invalid server response. Check Apache, PHP and MySQL.')}if(!d.ok)throw Error(d.message||'Request failed');return d}
async function init(){try{CSRF=(await (await fetch('api/api.php?action=csrf')).json()).csrf; if($('loginForm'))return;await refresh()}catch(e){toast(e.message,true)}}
async function refresh(silent=false){try{S=await api('state');renderAll()}catch(e){if(!silent)toast(e.message,true)}}
function renderAll(){S.market=!!S.market; $('marketStatus').textContent=S.market?'OPEN':'CLOSED';if($('marketSchedule')&&S.market_schedule){let z=S.market_schedule;$('marketSchedule').textContent=(z.manual_override===null?'Auto · ':'Override · ')+z.open_time.slice(0,5)+'–'+z.close_time.slice(0,5)+' · '+z.timezone.replace('Asia/Colombo','Sri Lanka');}$('statMarket').textContent=S.market?'Open':'Closed';$('statMyOrders').textContent=S.stats.open_orders||0;$('statTrades').textContent=S.stats.trade_count||0;$('statAlerts').textContent=S.stats.unread||0;$('notifCount').textContent=S.stats.unread||0;$('sideNotif').textContent=S.stats.unread||0;$('marketAction').textContent=S.market?'Close':'Open';$('bookMarketStatus').textContent=S.market?'Market is open':'Market is closed';renderDashboard();renderPriceBoards();renderMarket();renderOrders();renderBook();renderTrades();renderNotifications();}
function priceMeta(f){const last=Number(f.current_price??f.reference_price??0),prev=Number(f.previous_close??last),pct=Number(f.price_change_pct??0),high=Number(f.day_high??last),low=Number(f.day_low??last),vol=Number(f.volume??0);const dir=pct>0?'up':pct<0?'down':'flat';return {last,prev,pct,high,low,vol,dir}}
function changeText(m){return (m.pct>0?'▲ +':m.pct<0?'▼ ':'— ')+Math.abs(m.pct).toFixed(2)+'%'}
function renderPriceBoards(){const a=S.flowers||[];const board=$('priceBoard');if(board)board.innerHTML=a.map(f=>{const m=priceMeta(f);return `<div class="price-row"><div class="price-name"><img src="${img(f)}"><div><b>${esc(f.display_name)}</b><small>${esc(f.symbol.toUpperCase())} · ${f.lot_size} stems/lot</small></div></div><div><span class="price-last">Rs. ${money(m.last)}</span><small>Last traded</small></div><div class="price-change ${m.dir}"><b>${changeText(m)}</b><small>Prev. Rs. ${money(m.prev)}</small></div><div><b>Rs. ${money(m.high)}</b><small>Day high</small></div><div><b>Rs. ${money(m.low)}</b><small>Day low</small></div><div><b>${m.vol.toLocaleString('en-LK')}</b><small>Volume</small></div></div>`}).join('')||'<div class="empty">No market data yet.</div>';const ticker=$('marketTicker');if(ticker)ticker.innerHTML=a.map(f=>{const m=priceMeta(f);return `<div class="ticker-row"><div class="ticker-flower"><img src="${img(f)}"><b>${esc(f.display_name)}</b><small>${esc(f.symbol.toUpperCase())}</small></div><strong>Rs. ${money(m.last)}</strong><span class="price-change ${m.dir}">${changeText(m)}</span><span class="ticker-range">H ${money(m.high)} · L ${money(m.low)}</span></div>`}).join('')||'<div class="empty">No market prices yet.</div>'}
function renderDashboard(){let fs=S.flowers.slice(0,4);$('dashboardFlowers').innerHTML=fs.map(f=>{const m=priceMeta(f);return `<div class="mini-product"><img src="${img(f)}"><div><b>${esc(f.display_name)}</b><small>Minimum lot ${f.lot_size} · Last traded</small></div><strong>Rs. ${money(m.last)} <em class="mini-change ${m.dir}">${changeText(m)}</em></strong></div>`}).join('');if($('marketSnapshot'))$('marketSnapshot').innerHTML=S.flowers.slice(0,6).map(f=>{const m=priceMeta(f);return `<div class="activity"><span style="width:32px;height:32px;border-radius:9px;overflow:hidden;display:block;flex:none"><img src="${img(f)}" style="width:100%;height:100%;object-fit:cover"></span><div style="flex:1"><b>${esc(f.display_name)}</b><small>Prev. Rs. ${money(m.prev)} · H ${money(m.high)} · L ${money(m.low)}</small></div><strong class="price-change ${m.dir}">Rs. ${money(m.last)}<br><small>${changeText(m)}</small></strong></div>`}).join('')||'<div class="empty">No market data yet.</div>';if($('dashboardActivity'))$('dashboardActivity').innerHTML=(S.activity||[]).map(a=>`<div class="activity"><span class="dot"></span><div><b>${esc(a.message)}</b><small>${esc(a.created_at||'')}</small></div></div>`).join('')||'<div class="empty">No activity yet.</div>'}
function renderMarket(){if(!$('flowerGrid'))return;let q=($('flowerSearch').value||'').toLowerCase();let a=S.flowers.filter(f=>(f.display_name+' '+f.symbol).toLowerCase().includes(q));let sort=$('flowerSort').value;if(sort==='price_low')a.sort((x,y)=>priceMeta(x).last-priceMeta(y).last);if(sort==='price_high')a.sort((x,y)=>priceMeta(y).last-priceMeta(x).last);if(sort==='name')a.sort((x,y)=>x.display_name.localeCompare(y.display_name));$('flowerGrid').innerHTML=a.map(f=>{const m=priceMeta(f);return `<article class="product-card"><div class="product-img" style="background-image:url('${img(f)}')"><span>Live market lot</span><b>Rs. ${money(m.last)}</b></div><div class="product-body"><div class="product-label">${esc(f.symbol.toUpperCase())} · LAST TRADE</div><h3>${esc(f.display_name)}</h3><div class="market-price-line"><strong>Rs. ${money(m.last)}</strong><span class="price-change ${m.dir}">${changeText(m)}</span></div><div class="product-line"><span>Previous close</span><b>Rs. ${money(m.prev)}</b></div><div class="product-line"><span>Day range</span><b>${money(m.low)} – ${money(m.high)}</b></div><div class="product-line"><span>Volume</span><b>${m.vol.toLocaleString('en-LK')}</b></div><button class="btn small primary" onclick="openOrderModal('${esc(f.symbol)}')">Trade this flower</button></div></article>`}).join('')||'<div class="empty">No flowers match your search.</div>'}
function renderOrders(){let a=S.my_orders||[];$('ordersOpen').textContent=a.filter(x=>x.status==='OPEN').length;$('ordersFilled').textContent=a.filter(x=>x.status==='FILLED').length;$('ordersCancelled').textContent=a.filter(x=>x.status==='CANCELLED').length;$('myOrders').innerHTML=a.length?`<div class="table-head"><span>Order</span><span>Flower</span><span>Side</span><span>Qty</span><span>Price</span><span>Status</span><span></span></div>`+a.map(o=>`<div class="table-row"><span>#${o.id}<small>${o.created_at||''}</small></span><span><b>${esc(o.display_name)}</b><small>${esc(o.symbol)}</small></span><span><i class="side ${+o.side===1?'buy':'sell'}">${+o.side===1?'BUY':'SELL'}</i></span><span>${o.qty}<small>${o.remaining_qty} remaining</small></span><span>Rs. ${money(o.price)}</span><span><i class="status ${o.status.toLowerCase()}">${o.status}</i></span><span>${o.status==='OPEN'?`<button class="text-danger" onclick="cancelOrder(${o.id})">Cancel</button>`:''}</span></div>`).join(''):'<div class="empty">You have not placed any orders yet.</div>'}
function renderBook(){const make=(a)=>a.length?a.map(o=>`<div class="book-row"><div><b>${esc(o.display_name)}</b><small>#${o.id} · ${esc(o.trader_id)}</small></div><strong>${o.remaining_qty}</strong><span>Rs. ${money(o.price)}</span></div>`).join(''):'<div class="empty">No open orders.</div>';let buy=S.orders.filter(o=>+o.side===1).sort((a,b)=>+b.price-+a.price),sell=S.orders.filter(o=>+o.side===2).sort((a,b)=>+a.price-+b.price);$('buyBook').innerHTML=make(buy);$('sellBook').innerHTML=make(sell)}
function renderTrades(){$('tradesList').innerHTML=(S.trades||[]).map(t=>{let mine=F.trader===t.buyer_id||F.trader===t.seller_id;let confirmed=F.role==='buyer'?+t.buyer_confirmed:+t.seller_confirmed;return `<article class="trade-card"><div class="trade-badge">#${t.id}</div><div><span class="product-label">${esc(t.symbol.toUpperCase())}</span><h3>${t.qty} ${esc(t.display_name)}</h3><p>Buyer <b>${esc(t.buyer_id)}</b> · Seller <b>${esc(t.seller_id)}</b></p></div><div><b>Rs. ${money(t.price)}</b><small>Total Rs. ${money(t.price*t.qty)}</small></div><div><i class="status ${t.status.toLowerCase()}">${t.status}</i>${mine&&t.status!=='COMPLETED'&&!confirmed?`<button class="btn small primary" onclick="confirmTrade(${t.id})">Confirm my side</button>`:`<small>${confirmed?'Your side confirmed':''}</small>`}</div></article>`}).join('')||'<div class="empty">No matched trades yet.</div>'}
function renderNotifications(){$('notificationsList').innerHTML=(S.notifications||[]).map(n=>`<article class="notification ${+n.is_read?'read':'unread'}"><span class="notification-icon">✿</span><div><b>${esc(n.message)}</b><small>${esc(n.created_at||'')}</small></div></article>`).join('')||'<div class="empty">Your notification inbox is clear.</div>'}
function showPage(id){document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));$(id)?.classList.add('active');document.querySelectorAll('.side-nav button[data-page], .top-nav button[data-page]').forEach(b=>b.classList.toggle('active',b.dataset.page===id));if(innerWidth<900)$('sidebar')?.classList.remove('open');scrollTo({top:0,behavior:'smooth'})}
document.querySelectorAll('.side-nav button[data-page], .top-nav button[data-page]').forEach(b=>b.onclick=()=>showPage(b.dataset.page));
function globalSearch(){let q=$('globalSearch').value.trim();showPage('market');$('flowerSearch').value=q;renderMarket()}
const marketAction=$('marketAction');if(marketAction){marketAction.onclick=async()=>{try{let d=await api('toggle_market',{method:'POST',body:new FormData()});toast(d.message);await refresh()}catch(e){toast(e.message,true)}};}
function openOrderModal(symbol=''){if(!S.market){toast('The market is currently closed.',true);return}let f=S.flowers.find(x=>x.symbol===symbol)||S.flowers[0];$('orderSymbol').innerHTML=S.flowers.map(x=>`<option value="${esc(x.symbol)}">${esc(x.display_name)} · Lot ${x.lot_size}</option>`).join('');$('orderSide').innerHTML=F.role==='buyer'?'<option value="1">BUY — I want to purchase</option>':'<option value="2">SELL — I want to supply</option>';if(f)$('orderSymbol').value=f.symbol;syncOrder();$('orderMsg').textContent='';$('modal').classList.add('show')}
function closeModal(){$('modal').classList.remove('show')}
function syncOrder(){let f=S.flowers.find(x=>x.symbol===$('orderSymbol').value);if(!f)return;$('orderQty').value=f.lot_size;$('orderPrice').value=f.reference_price;calcTotal()}
$('orderSymbol')?.addEventListener('change',syncOrder);$('orderQty')?.addEventListener('input',calcTotal);$('orderPrice')?.addEventListener('input',calcTotal);function calcTotal(){$('orderTotal').textContent='Rs. '+money((+$('orderQty').value||0)*(+$('orderPrice').value||0))}
$('orderForm')?.addEventListener('submit',async e=>{e.preventDefault();try{let d=await api('add_order',{method:'POST',body:new FormData(e.target)});closeModal();toast(d.message);await refresh()}catch(x){$('orderMsg').textContent=x.message}});
async function cancelOrder(id){if(!confirm('Cancel this open order?'))return;let f=new FormData();f.append('order_id',id);try{toast((await api('cancel_order',{method:'POST',body:f})).message);await refresh()}catch(e){toast(e.message,true)}}
async function confirmTrade(id){let f=new FormData();f.append('trade_id',id);try{toast((await api('confirm_trade',{method:'POST',body:f})).message);await refresh()}catch(e){toast(e.message,true)}}
async function markNotifications(){try{await api('mark_notifications',{method:'POST',body:new FormData()});await refresh()}catch(e){toast(e.message,true)}}
$('profileForm')?.addEventListener('submit',async e=>{e.preventDefault();try{let d=await api('update_profile',{method:'POST',body:new FormData(e.target)});toast(d.message);location.reload()}catch(x){$('profileMsg').textContent=x.message}});
async function logout(){try{await api('logout',{method:'POST',body:new FormData()});location.reload()}catch(e){toast(e.message,true)}}
function toggleSidebar(){$('sidebar')?.classList.toggle('open')}
function fillDemo(role){document.querySelectorAll('.role-btn').forEach(b=>b.classList.toggle('active',b.dataset.role===role));$('loginRole').value=role;document.querySelector('#loginForm input[name=email]').value=role==='buyer'?'buyer@florax.test':'seller@florax.test';document.querySelector('#loginForm input[name=password]').value=role==='buyer'?'Buyer@12345':'Seller@12345';document.querySelector('.auth-tab[data-pane="login"]').click();document.querySelector('#loginForm .primary').textContent='Sign in as '+(role==='buyer'?'Buyer':'Seller')}
function toast(msg,bad=false){let t=document.createElement('div');t.className='toast '+(bad?'bad':'');t.textContent=msg;$('toastArea').appendChild(t);requestAnimationFrame(()=>t.classList.add('show'));setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),250)},3200)}
function money(v){return Number(v||0).toLocaleString('en-LK',{minimumFractionDigits:2,maximumFractionDigits:2})}function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}
if($('loginForm')){
  const tabs=[...document.querySelectorAll('.auth-tab')];
  const panes=[...document.querySelectorAll('.auth-pane')];

  function showAuthTab(name){
    tabs.forEach(t=>{
      const active=t.getAttribute('data-pane')===name;
      t.classList.toggle('active',active);
      t.setAttribute('aria-selected',active?'true':'false');
    });
    panes.forEach(p=>{
      const active=p.id===name+'Pane';
      p.classList.toggle('active',active);
      p.hidden=!active;
    });
  }

  tabs.forEach(t=>{
    t.type='button';
    t.addEventListener('click',function(e){
      e.preventDefault();
      showAuthTab(this.getAttribute('data-pane'));
    });
  });

  document.querySelectorAll('.role-btn').forEach(b=>{
    b.type='button';
    b.addEventListener('click',function(e){
      e.preventDefault();
      document.querySelectorAll('.role-btn').forEach(x=>x.classList.remove('active'));
      this.classList.add('active');
      if($('loginRole')) $('loginRole').value=this.dataset.role;
      const btn=$('loginForm').querySelector('.primary');
      if(btn) btn.textContent='Sign in as '+(this.dataset.role==='buyer'?'Buyer':'Seller');
    });
  });

  showAuthTab('login');

  $('loginForm').addEventListener('submit',async e=>{
    e.preventDefault();
    const m=$('loginMsg');
    try{
      m.textContent='Signing in…';
      await api('login',{method:'POST',body:new FormData(e.target)});
      location.href='index.php';
    }catch(x){m.textContent=x.message;}
  });

  $('registerForm').addEventListener('submit',async e=>{
    e.preventDefault();
    const m=$('registerMsg');
    try{
      m.textContent='Creating account…';
      const d=await api('register',{method:'POST',body:new FormData(e.target)});
      m.textContent='Account created successfully. Opening your workspace… Trader ID: '+d.trader_id;
      setTimeout(()=>{location.href='index.php';},500);
    }catch(x){m.textContent=x.message;const submit=e.target.querySelector('button[type=submit]');if(submit)submit.disabled=false;}
  });
}

window.toggleTheme=function(){document.body.classList.toggle('dark-mode');const dark=document.body.classList.contains('dark-mode');const b=document.getElementById('themeToggle');if(b)b.innerHTML=dark?'☀ <span>Light</span>':'☾ <span>Dark</span>';try{localStorage.setItem('florax-theme',dark?'dark':'light')}catch(e){}};
try{if(localStorage.getItem('florax-theme')==='dark'){document.body.classList.add('dark-mode');const b=document.getElementById('themeToggle');if(b)b.innerHTML='☀ <span>Light</span>'}}catch(e){}
init();if(!$('loginForm'))setInterval(()=>refresh(true),5000);


// Lucide icon rendering and graceful fallback if the CDN is unavailable.
function refreshFloraXIcons(){ if(window.lucide && typeof window.lucide.createIcons==='function'){ window.lucide.createIcons({attrs:{'stroke-width':1.8}}); } }
document.addEventListener('DOMContentLoaded', refreshFloraXIcons);
setTimeout(refreshFloraXIcons, 500);
