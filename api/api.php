<?php
session_start();
require_once __DIR__.'/../config.php';
header('Content-Type: application/json; charset=utf-8');
function out($d,$c=200){http_response_code($c);echo json_encode($d,JSON_UNESCAPED_UNICODE);exit;}
function fail($m,$c=400){out(['ok'=>false,'message'=>$m],$c);}
function user(){return $_SESSION['user']??null;}
function mustLogin(){if(!user())fail('Please sign in first.',401);return user();}
function mustRole($roles){$u=mustLogin();if(!in_array($u['role'],$roles,true))fail('This feature is not available for your account role.',403);return $u;}
function audit($pdo,$msg){$pdo->prepare('INSERT INTO activity_log(message) VALUES(?)')->execute([$msg]);}
function notify($pdo,$trader,$msg){$pdo->prepare('INSERT INTO notifications(trader_id,message) VALUES(?,?)')->execute([$trader,$msg]);}
function getUserById($pdo,$id){$s=$pdo->prepare('SELECT id,trader_id,role,full_name,business_name,phone,email,district,address,created_at FROM users WHERE id=?');$s->execute([$id]);return $s->fetch();}
try{
 $action=$_GET['action']??'';
 if($action==='csrf') out(['ok'=>true,'csrf'=>csrf_token()]);
 $pdo=db();
 if(in_array($action,['register','login','logout','update_profile','toggle_market','add_order','cancel_order','confirm_trade','mark_notifications','add_flower'],true)) require_csrf();
 if($action==='register'){
  $role=$_POST['role']??'';$full=trim($_POST['full_name']??'');$business=trim($_POST['business_name']??'');$phone=trim($_POST['phone']??'');$email=strtolower(trim($_POST['email']??''));$district=trim($_POST['district']??'');$address=trim($_POST['address']??'');$pw=$_POST['password']??'';$cp=$_POST['password_confirm']??'';
  if(!in_array($role,['buyer','seller'],true))fail('Choose Buyer or Seller.');
  if(strlen($full)<2||strlen($business)<2||strlen($phone)<7||$district===''||$address==='')fail('Please complete all required fields.');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))fail('Enter a valid email address.'); if(strlen($pw)<8)fail('Password must contain at least 8 characters.'); if($pw!==$cp)fail('Passwords do not match.');
  $s=$pdo->prepare('SELECT id FROM users WHERE email=?');$s->execute([$email]);if($s->fetch())fail('An account already exists with this email.');
  $prefix=$role==='buyer'?'BUYER':'SELLER';$n=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='".$role."'")->fetchColumn()+1;$trader=$prefix.str_pad($n,4,'0',STR_PAD_LEFT);
  while(true){$q=$pdo->prepare('SELECT id FROM users WHERE trader_id=?');$q->execute([$trader]);if(!$q->fetch())break;$n++;$trader=$prefix.str_pad($n,4,'0',STR_PAD_LEFT);}
  $pdo->prepare('INSERT INTO users(trader_id,role,full_name,business_name,phone,email,password_hash,district,address) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$trader,$role,$full,$business,$phone,$email,password_hash($pw,PASSWORD_DEFAULT),$district,$address]);
  $_SESSION['user']=getUserById($pdo,$pdo->lastInsertId()); session_regenerate_id(true); audit($pdo,"New $role account registered: $trader"); notify($pdo,$trader,'Welcome to FloraX. Your trader ID is '.$trader.'.'); out(['ok'=>true,'message'=>'Account created successfully.','trader_id'=>$trader]);
 }
 if($action==='login'){
  $email=strtolower(trim($_POST['email']??''));$pw=$_POST['password']??'';$role=$_POST['role']??'';$s=$pdo->prepare('SELECT * FROM users WHERE email=? AND active=1');$s->execute([$email]);$u=$s->fetch();
  if(!$u||!password_verify($pw,$u['password_hash']))fail('Incorrect email or password.',401);if($role!==$u['role'])fail('Choose the correct login type for this account.',403);
  session_regenerate_id(true);$_SESSION['user']=getUserById($pdo,$u['id']);audit($pdo,$u['trader_id'].' signed in');out(['ok'=>true,'message'=>'Login successful.']);
 }
 if($action==='logout'){audit($pdo,(user()['trader_id']??'User').' signed out');$_SESSION=[];session_destroy();out(['ok'=>true]);}
 if($action==='state'){
  $u=mustLogin();$ms=$pdo->query('SELECT * FROM market_status WHERE id=1')->fetch();$tz=new DateTimeZone($ms['timezone']??'Asia/Colombo');$now=new DateTime('now',$tz);$dow=(int)$now->format('N');$clock=$now->format('H:i:s');$scheduled=$ms['schedule_enabled'] && $dow<=5 && $clock>=$ms['open_time'] && $clock<$ms['close_time'];$market=$ms['manual_override']===null?$scheduled:(bool)$ms['manual_override'];$flowers=$pdo->query("SELECT f.*,mp.current_price,mp.previous_close,mp.day_open,mp.day_high,mp.day_low,mp.volume,mp.last_trade_at,ROUND(((mp.current_price-mp.previous_close)/NULLIF(mp.previous_close,0))*100,2) AS price_change_pct FROM flowers f JOIN market_prices mp ON mp.symbol=f.symbol WHERE f.active=1 ORDER BY f.display_name")->fetchAll();
  $s=$pdo->prepare("SELECT o.*,f.display_name FROM orders o JOIN flowers f ON f.symbol=o.symbol WHERE o.trader_id=? ORDER BY o.id DESC LIMIT 100");$s->execute([$u['trader_id']]);$myOrders=$s->fetchAll();
  $orders=$pdo->query("SELECT o.*,f.display_name FROM orders o JOIN flowers f ON f.symbol=o.symbol WHERE o.status='OPEN' ORDER BY o.symbol, o.side, o.price DESC, o.created_at ASC")->fetchAll();
  $s=$pdo->prepare('SELECT t.*,f.display_name FROM trades t JOIN flowers f ON f.symbol=t.symbol WHERE t.buyer_id=? OR t.seller_id=? ORDER BY t.id DESC LIMIT 100');$s->execute([$u['trader_id'],$u['trader_id']]);$trades=$s->fetchAll();
  $s=$pdo->prepare('SELECT * FROM notifications WHERE trader_id=? ORDER BY id DESC LIMIT 80');$s->execute([$u['trader_id']]);$notes=$s->fetchAll();
  $activity=$pdo->query('SELECT * FROM activity_log ORDER BY id DESC LIMIT 12')->fetchAll();
  $stats=['open_orders'=>0,'filled_orders'=>0,'cancelled_orders'=>0,'unread'=>0,'trade_count'=>count($trades)];foreach($myOrders as $o){if($o['status']==='OPEN')$stats['open_orders']++;elseif($o['status']==='FILLED')$stats['filled_orders']++;else $stats['cancelled_orders']++;}$s=$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE trader_id=? AND is_read=0');$s->execute([$u['trader_id']]);$stats['unread']=(int)$s->fetchColumn();
  out(['ok'=>true,'user'=>$u,'market'=>$market,'market_schedule'=>['open_time'=>$ms['open_time'],'close_time'=>$ms['close_time'],'timezone'=>$ms['timezone'],'schedule_enabled'=>(bool)$ms['schedule_enabled'],'manual_override'=>$ms['manual_override']],'flowers'=>$flowers,'my_orders'=>$myOrders,'orders'=>$orders,'trades'=>$trades,'notifications'=>$notes,'activity'=>$activity,'stats'=>$stats]);
 }
 if($action==='toggle_market'){
  $u=mustRole(['seller','admin']);$ms=$pdo->query('SELECT * FROM market_status WHERE id=1')->fetch();$tz=new DateTimeZone($ms['timezone']??'Asia/Colombo');$now=new DateTime('now',$tz);$dow=(int)$now->format('N');$clock=$now->format('H:i:s');$scheduled=(bool)$ms['schedule_enabled'] && $dow<=5 && $clock>=$ms['open_time'] && $clock<$ms['close_time'];$cur=$ms['manual_override']===null?$scheduled:(bool)$ms['manual_override'];$new=!$cur;if($new){$pdo->exec('UPDATE market_prices SET previous_close=current_price,day_open=current_price,day_high=current_price,day_low=current_price,volume=0,last_trade_at=NULL');}$pdo->prepare('UPDATE market_status SET is_open=?,manual_override=?,updated_at=NOW() WHERE id=1')->execute([$new,$new?1:0]);audit($pdo,$u['trader_id'].' '.($new?'opened':'closed').' the market');
  if(!$new)$pdo->exec("UPDATE orders SET status='CANCELLED',remaining_qty=0 WHERE status='OPEN'");out(['ok'=>true,'market'=>$new,'message'=>'Market '.($new?'opened':'closed').'.']);
 }
 if($action==='add_order'){
  $u=mustRole(['buyer','seller']);$symbol=strtolower(trim($_POST['symbol']??''));$side=(int)($_POST['side']??0);$qty=(int)($_POST['qty']??0);$price=(float)($_POST['price']??0);$market=(bool)$pdo->query('SELECT is_open FROM market_status WHERE id=1')->fetchColumn();if(!$market)fail('Market is closed.');
  if(($u['role']==='buyer'&&$side!==1)||($u['role']==='seller'&&$side!==2))fail('Your role only allows '.($u['role']==='buyer'?'BUY':'SELL').' orders.');
  $s=$pdo->prepare('SELECT * FROM flowers WHERE symbol=? AND active=1');$s->execute([$symbol]);$f=$s->fetch();if(!$f)fail('Flower not found.');if($qty<1||$qty%(int)$f['lot_size']!==0)fail('Quantity must be a multiple of '.$f['lot_size'].'.');if($price<=0)fail('Enter a valid price.');
  $pdo->beginTransaction();$pdo->prepare("INSERT INTO orders(trader_id,symbol,qty,remaining_qty,price,side,status) VALUES(?,?,?,?,?,?, 'OPEN')")->execute([$u['trader_id'],$symbol,$qty,$qty,$price,$side]);$newId=(int)$pdo->lastInsertId();notify($pdo,$u['trader_id'],'Order #'.$newId.' accepted: '.($side===1?'BUY ':'SELL ').$qty.' '.$f['display_name'].' @ Rs. '.number_format($price,2));audit($pdo,$u['trader_id'].' placed '.($side===1?'BUY':'SELL').' order #'.$newId);
  $remain=$qty;$opp=$side===1?2:1;$sql="SELECT * FROM orders WHERE symbol=? AND side=? AND status='OPEN' AND remaining_qty>0 AND trader_id<>? AND price".($side===1?'<=':'>=')."? ORDER BY price ".($side===1?'ASC':'DESC').",created_at ASC,id ASC FOR UPDATE";$s=$pdo->prepare($sql);$s->execute([$symbol,$opp,$u['trader_id'],$price]);
  while($remain>0&&($m=$s->fetch())){$q=min($remain,(int)$m['remaining_qty']);$buyer=$side===1?$u['trader_id']:$m['trader_id'];$seller=$side===2?$u['trader_id']:$m['trader_id'];$tradePrice=(float)$m['price'];$buyId=$side===1?$newId:$m['id'];$sellId=$side===2?$newId:$m['id'];$pdo->prepare('INSERT INTO trades(buy_order_id,sell_order_id,buyer_id,seller_id,symbol,qty,price,buyer_confirmed,seller_confirmed,status) VALUES(?,?,?,?,?,?,?,0,0,\'PENDING\')')->execute([$buyId,$sellId,$buyer,$seller,$symbol,$q,$tradePrice]);$tid=(int)$pdo->lastInsertId();$pdo->prepare("UPDATE orders SET status=IF(remaining_qty-?<=0,'FILLED','OPEN'),remaining_qty=remaining_qty-? WHERE id=?")->execute([$q,$q,$m['id']]);$pdo->prepare('UPDATE market_prices SET current_price=?,day_high=GREATEST(day_high,?),day_low=LEAST(day_low,?),volume=volume+?,last_trade_at=NOW() WHERE symbol=?')->execute([$tradePrice,$tradePrice,$tradePrice,$q,$symbol]);$remain-=$q;notify($pdo,$buyer,"Trade #$tid matched: $q {$f['display_name']} @ Rs. ".number_format($tradePrice,2));notify($pdo,$seller,"Trade #$tid matched: $q {$f['display_name']} @ Rs. ".number_format($tradePrice,2));audit($pdo,"Trade #$tid matched");}
  $pdo->prepare('UPDATE orders SET remaining_qty=?,status=? WHERE id=?')->execute([$remain,$remain===0?'FILLED':'OPEN',$newId]);$pdo->commit();out(['ok'=>true,'message'=>'Order accepted. Matching completed.']);
 }
 if($action==='cancel_order'){
  $u=mustRole(['buyer','seller']);$id=(int)($_POST['order_id']??0);$s=$pdo->prepare("SELECT * FROM orders WHERE id=? AND trader_id=? AND status='OPEN'");$s->execute([$id,$u['trader_id']]);$o=$s->fetch();if(!$o)fail('Open order not found.');$pdo->prepare("UPDATE orders SET status='CANCELLED',remaining_qty=0 WHERE id=?")->execute([$id]);notify($pdo,$u['trader_id'],'Order #'.$id.' cancelled.');audit($pdo,$u['trader_id'].' cancelled order #'.$id);out(['ok'=>true,'message'=>'Order cancelled.']);
 }
 if($action==='confirm_trade'){
  $u=mustRole(['buyer','seller']);$id=(int)($_POST['trade_id']??0);$s=$pdo->prepare('SELECT * FROM trades WHERE id=? AND (buyer_id=? OR seller_id=?)');$s->execute([$id,$u['trader_id'],$u['trader_id']]);$t=$s->fetch();if(!$t)fail('Trade not found.');if($t['status']==='COMPLETED')fail('Trade already completed.');$field=$u['role']==='buyer'?'buyer_confirmed':'seller_confirmed';$pdo->prepare("UPDATE trades SET $field=1 WHERE id=?")->execute([$id]);$s=$pdo->prepare('SELECT * FROM trades WHERE id=?');$s->execute([$id]);$t=$s->fetch();$status=((int)$t['buyer_confirmed']&&(int)$t['seller_confirmed'])?'COMPLETED':'PENDING';$pdo->prepare('UPDATE trades SET status=? WHERE id=?')->execute([$status,$id]);foreach([$t['buyer_id'],$t['seller_id']] as $who)notify($pdo,$who,$status==='COMPLETED'?"Trade #$id fully confirmed and completed.":"Trade #$id confirmation recorded. Waiting for the other party.");audit($pdo,$u['trader_id'].' confirmed trade #'.$id);out(['ok'=>true,'message'=>$status==='COMPLETED'?'Trade completed.':'Confirmation recorded.']);
 }
 if($action==='mark_notifications'){ $u=mustLogin();$pdo->prepare('UPDATE notifications SET is_read=1 WHERE trader_id=?')->execute([$u['trader_id']]);out(['ok'=>true]); }
 if($action==='update_profile'){$u=mustLogin();$full=trim($_POST['full_name']??'');$business=trim($_POST['business_name']??'');$phone=trim($_POST['phone']??'');$district=trim($_POST['district']??'');$address=trim($_POST['address']??'');if(strlen($full)<2||strlen($business)<2||strlen($phone)<7||$district===''||$address==='')fail('Please complete all profile fields.');$pdo->prepare('UPDATE users SET full_name=?,business_name=?,phone=?,district=?,address=? WHERE id=?')->execute([$full,$business,$phone,$district,$address,$u['id']]);$_SESSION['user']=getUserById($pdo,$u['id']);notify($pdo,$u['trader_id'],'Your profile was updated.');out(['ok'=>true,'user'=>$_SESSION['user'],'message'=>'Profile updated.']);}
 if($action==='add_flower'){mustRole(['admin']);$symbol=strtolower(trim($_POST['symbol']??''));$name=trim($_POST['display_name']??'');$lot=(int)($_POST['lot_size']??0);$price=(float)($_POST['reference_price']??0);if(!preg_match('/^[a-z0-9_-]+$/',$symbol)||$name===''||$lot<1||$price<=0)fail('Enter valid flower details.');$pdo->prepare('INSERT INTO flowers(symbol,display_name,lot_size,reference_price,image_url,active) VALUES(?,?,?,?,?,1)')->execute([$symbol,$name,$lot,$price,trim($_POST['image_url']??'' )]);audit($pdo,'Admin added flower '.$name);out(['ok'=>true,'message'=>'Flower added.']);}
 fail('Unknown action.',404);
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();fail('Server error: '.$e->getMessage(),500);}
