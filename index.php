<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Kuala_Lumpur');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 1800) {
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$_SESSION['last_seen'] = time();
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function field(string $key, int $max=1000): string {
    $value = $_POST[$key] ?? '';
    if (!is_string($value) || strlen(trim($value)) > $max) throw new RuntimeException('Invalid '.str_replace('_',' ',$key).'.');
    return trim($value);
}
function redirect(): never { header('Location: index.php', true, 303); exit; }
function query(PDO $db, string $sql, array $args=[]): PDOStatement { $s=$db->prepare($sql); $s->execute($args); return $s; }
function validDate(string $value): bool {
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
    return $d !== false && $d->format('Y-m-d') === $value;
}
$error = null; $connectionError = false; $db = null; $user = null; $hasAdmin = false;
try {
    $configFile=__DIR__.'/config.local.php';
    if (!is_file($configFile)) throw new RuntimeException('Copy config.example.php to config.local.php and enter your Laragon database settings. Then import database/schema.sql into the booking database.');
    $c=require $configFile;
    $db=new PDO('mysql:host='.$c['host'].';port='.(int)$c['port'].';dbname='.$c['database'].';charset=utf8mb4',$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $hasAdmin=(bool)query($db,"SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
    if (isset($_SESSION['user_id'])) $user=query($db,'SELECT id,name,email,role FROM users WHERE id=?',[$_SESSION['user_id']])->fetch() ?: null;
} catch (Throwable $ex) {
    $connectionError=true;
    $error=isset($configFile) && !is_file($configFile) ? $ex->getMessage() : 'Database connection or tables are not ready. Check config.local.php, start MySQL in Laragon and import database/schema.sql. Your MySQL port may be 3308.';
}
if ($_SERVER['REQUEST_METHOD']==='POST' && !$connectionError) {
    try {
        if (!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['csrf'],$_POST['csrf'])) throw new RuntimeException('Your session expired. Refresh this page and try again.');
        $action=field('action',40);
        if ($action==='login') {
            $email=strtolower(field('email',254));
            $password=field('password',256);
            $account=query($db,'SELECT * FROM users WHERE email=?',[$email])->fetch();
            if (!$account || !password_verify($password,$account['password_hash'])) throw new RuntimeException('Incorrect email or password.');
            session_regenerate_id(true); $_SESSION['user_id']=$account['id']; $_SESSION['csrf']=bin2hex(random_bytes(32));
            redirect();
        } elseif ($action==='register' || $action==='setup') {
            $name=field('name',120); $email=strtolower(field('email',254)); $password=field('password',256);
            if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<10) throw new RuntimeException('Enter your name, a valid email and a password of at least 10 characters.');
            if ($action==='setup') {
                // Serialise first-admin creation across simultaneous requests.
                if ((int)query($db,"SELECT GET_LOCK('pdkt_first_admin',5)")->fetchColumn() !== 1) throw new RuntimeException('Setup is busy. Please try again.');
                try {
                    if (query($db,"SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch()) throw new RuntimeException('An administrator already exists. Please sign in.');
                    query($db,'INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),'admin']);
                } finally { query($db,"SELECT RELEASE_LOCK('pdkt_first_admin')"); }
            } else {
                if (!$hasAdmin) throw new RuntimeException('Create the first administrator before registering staff.');
                query($db,'INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),'staff']);
            }
            $_SESSION['flash']='Account created. You can now sign in.'; redirect();
        } else {
            if (!$user) throw new RuntimeException('Please sign in first.');
            if ($action==='logout') {
                $_SESSION=[]; session_regenerate_id(true); redirect();
            } elseif ($action==='equipment') {
                if ($user['role']!=='admin') throw new RuntimeException('Administrator access required.');
                $name=field('name',120); $asset=field('asset_number',80); $category=field('category',20);
                if ($name==='' || $asset==='' || !in_array($category,['Laptop','Phone','Camera'],true)) throw new RuntimeException('Enter valid equipment details.');
                query($db,'INSERT INTO equipment(name,category,asset_number) VALUES(?,?,?)',[$name,$category,$asset]);
                $_SESSION['flash']='Equipment added.';
            } elseif ($action==='book') {
                $start=field('start_date',10); $end=field('end_date',10); $purpose=field('purpose');
                if (!validDate($start) || !validDate($end) || $start<date('Y-m-d') || $end<$start || $purpose==='') throw new RuntimeException('Choose valid dates and enter the purpose of use.');
                $db->beginTransaction();
                $equipment=query($db,'SELECT id FROM equipment WHERE id=? FOR UPDATE',[field('equipment_id',20)])->fetch();
                if (!$equipment) throw new RuntimeException('Equipment not found.');
                if (query($db,"SELECT id FROM bookings WHERE equipment_id=? AND status IN ('Approved','Collected') AND start_date<=? AND end_date>=? FOR UPDATE",[$equipment['id'],$end,$start])->fetch()) throw new RuntimeException('This equipment is already booked for those dates.');
                query($db,'INSERT INTO bookings(user_id,equipment_id,start_date,end_date,purpose) VALUES(?,?,?,?,?)',[$user['id'],$equipment['id'],$start,$end,$purpose]);
                $db->commit(); $_SESSION['flash']='Request submitted. Please wait for administrator approval.';
            } elseif ($action==='status') {
                $id=field('booking_id',20); $change=field('change',20);
                $transitions=['approve'=>['Pending approval','Approved'],'reject'=>['Pending approval','Rejected'],'collect'=>['Approved','Collected'],'return'=>['Collected','Returned'],'cancel'=>['Pending approval','Cancelled']];
                if (!isset($transitions[$change])) throw new RuntimeException('Invalid action.');
                $db->beginTransaction();
                $b=query($db,'SELECT * FROM bookings WHERE id=?',[$id])->fetch();
                if (!$b) throw new RuntimeException('Booking not found.');
                // All availability mutations lock the equipment first, then its bookings.
                query($db,'SELECT id FROM equipment WHERE id=? FOR UPDATE',[$b['equipment_id']]);
                $b=query($db,'SELECT * FROM bookings WHERE id=? FOR UPDATE',[$id])->fetch();
                if ($b['status']!==$transitions[$change][0]) throw new RuntimeException('This booking status has changed. Refresh and try again.');
                if (($change==='cancel' && (int)$b['user_id']!==(int)$user['id']) || ($change!=='cancel' && $user['role']!=='admin')) throw new RuntimeException('You do not have permission for this action.');
                if ($change==='approve' && $b['end_date']<date('Y-m-d')) throw new RuntimeException('This booking has already expired.');
                if ($change==='approve' && query($db,"SELECT id FROM bookings WHERE equipment_id=? AND id<>? AND status IN ('Approved','Collected') AND start_date<=? AND end_date>=? FOR UPDATE",[$b['equipment_id'],$id,$b['end_date'],$b['start_date']])->fetch()) throw new RuntimeException('Cannot approve: these dates overlap an approved booking.');
                if ($change==='collect' && query($db,"SELECT id FROM bookings WHERE equipment_id=? AND status='Collected' FOR UPDATE",[$b['equipment_id']])->fetch()) throw new RuntimeException('The previous borrower has not returned this equipment.');
                query($db,'UPDATE bookings SET status=? WHERE id=?',[$transitions[$change][1],$id]);
                $db->commit(); $_SESSION['flash']='Booking updated.';
            } else throw new RuntimeException('Unknown action.');
            redirect();
        }
    } catch (Throwable $ex) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(400);
        $error=$ex instanceof PDOException ? ((string)$ex->getCode()==='23000' ? 'That email or asset number is already registered.' : 'The database request failed. Please try again.') : $ex->getMessage();
    }
}
$flash=$_SESSION['flash']??null; unset($_SESSION['flash']);
function accountFields(): void { ?>
<label>Full name<input name="name" required maxlength="120" autocomplete="name"></label>
<label>Email<input type="email" name="email" required maxlength="254" autocomplete="email"></label>
<label>Password <small>At least 10 characters</small><input type="password" name="password" required minlength="10" maxlength="256" autocomplete="new-password"></label>
<?php }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PDKT Equipment Booking System</title><link rel="stylesheet" href="assets/style.css"><script src="assets/app.js" defer></script></head>
<body><header><div class="header-inner"><span class="brand">PDKT <span class="brand-label">STAFF SERVICES</span></span><span class="office">Pejabat Daerah Kuala Terengganu</span></div></header>
<main><div class="intro"><span class="eyebrow">EQUIPMENT MANAGEMENT</span><h1>Equipment Booking System</h1><p>Book the tools you need. Keep every loan organised.</p></div>
<?php if ($error): ?><div class="notice error" role="alert"><?=e($error)?></div><?php endif ?>
<?php if ($flash): ?><div class="notice success" role="status"><?=e($flash)?></div><?php endif ?>
<?php if ($connectionError): ?><section><h2>Connect your database</h2><p>Follow the Laragon setup steps in README.md. No tables or accounts will be created automatically.</p></section>
<?php elseif (!$hasAdmin): ?><section class="narrow"><span class="badge">FIRST-TIME SETUP</span><h2>Welcome to PDKT</h2><p>Create the first administrator account. Keep these details private.</p><form method="post"><?=csrf()?><input type="hidden" name="action" value="setup"><?php accountFields() ?><button>Create administrator account</button></form></section>
<?php elseif (!$user): ?><div class="grid"><section><h2>Welcome back</h2><p>Sign in to manage your equipment requests.</p><form method="post"><?=csrf()?><input type="hidden" name="action" value="login"><label>Email<input type="email" name="email" required autocomplete="username"></label><label>Password<input type="password" name="password" required maxlength="256" autocomplete="current-password"></label><button>Sign in</button></form></section><section><h2>New staff member?</h2><p>Create an account to request a laptop, phone or camera.</p><form method="post"><?=csrf()?><input type="hidden" name="action" value="register"><?php accountFields() ?><button class="secondary">Register as staff</button></form></section></div>
<?php else:
$equipment=query($db,'SELECT * FROM equipment ORDER BY category,name')->fetchAll();
$bookings=query($db,'SELECT b.*,u.name AS staff,e.name AS equipment_name,e.asset_number,e.category FROM bookings b JOIN users u ON u.id=b.user_id JOIN equipment e ON e.id=b.equipment_id'.($user['role']==='admin'?'':' WHERE b.user_id=?').' ORDER BY b.id DESC',$user['role']==='admin'?[]:[$user['id']])->fetchAll();
?>
<section class="welcome"><div><span class="badge"><?= $user['role']==='admin'?'Administrator':'Staff' ?></span><h2>Hello, <?=e($user['name'])?></h2><p>Every equipment request requires administrator approval.</p></div><form method="post"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="secondary">Sign out</button></form></section>
<div class="stats"><?php foreach (['Laptop','Phone','Camera'] as $category): ?><div><span><?=e($category)?> inventory</span><strong><?=count(array_filter($equipment,fn($item)=>$item['category']===$category))?></strong><small>Registered units</small></div><?php endforeach ?></div>
<?php if ($user['role']==='admin'): ?><section><h2>Register equipment</h2><p>Add each item using its unique asset number.</p><form method="post"><?=csrf()?><input type="hidden" name="action" value="equipment"><div class="grid three"><label>Equipment name<input name="name" required maxlength="120" placeholder="e.g. Office laptop"></label><label>Category<select name="category"><option>Laptop</option><option>Phone</option><option>Camera</option></select></label><label>Asset number<input name="asset_number" required maxlength="80" placeholder="Enter actual asset number"></label></div><button>Add equipment</button></form></section><?php endif ?>
<section><h2>New booking request</h2><?php if (!$equipment): ?><p>No equipment is registered yet. An administrator must add equipment before staff can book.</p><?php else: ?><p>Choose an item and your dates. We will send your request for approval.</p><form method="post" id="booking-form"><?=csrf()?><input type="hidden" name="action" value="book"><label>Equipment<select name="equipment_id"><?php foreach ($equipment as $item): ?><option value="<?=e($item['id'])?>"><?=e($item['category'].' — '.$item['name'].' ('.$item['asset_number'].')')?></option><?php endforeach ?></select></label><div class="grid"><label>Collection date<input type="date" name="start_date" id="start-date" min="<?=date('Y-m-d')?>" required></label><label>Return date<input type="date" name="end_date" id="end-date" min="<?=date('Y-m-d')?>" required></label></div><label>Purpose of use<textarea name="purpose" required maxlength="1000" rows="3" placeholder="Tell us what you need the equipment for"></textarea></label><button>Submit for approval</button></form><?php endif ?></section>
<section><h2><?=$user['role']==='admin'?'All requests':'My requests'?></h2><?php if (!$bookings): ?><div class="empty"><h3>No requests yet</h3><p>Your equipment requests and their status will appear here.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Equipment / Staff</th><th>Dates</th><th>Purpose</th><th>Status / Actions</th></tr></thead><tbody><?php foreach ($bookings as $b): ?><tr><td><strong><?=e($b['equipment_name'])?></strong><br><small><?=e($b['asset_number'])?></small><br><?=e($b['staff'])?></td><td><?=e($b['start_date'])?><br><small>to <?=e($b['end_date'])?></small></td><td><?=e($b['purpose'])?></td><td><span class="badge status-<?=strtolower(explode(' ',$b['status'])[0])?>"><?=e($b['status'])?></span><div class="actions"><?php
$actions=[];
if ($user['role']==='admin') $actions=match($b['status']) {'Pending approval'=>['approve'=>'Approve','reject'=>'Reject'],'Approved'=>['collect'=>'Record collection'],'Collected'=>['return'=>'Record return'],default=>[]};
if ((int)$user['id']===(int)$b['user_id'] && $b['status']==='Pending approval') $actions['cancel']='Cancel';
foreach ($actions as $key=>$label): ?><form method="post"><?=csrf()?><input type="hidden" name="action" value="status"><input type="hidden" name="booking_id" value="<?=e($b['id'])?>"><button class="small <?=$key==='reject'||$key==='cancel'?'secondary':''?>" name="change" value="<?=e($key)?>"><?=e($label)?></button></form><?php endforeach ?></div></td></tr><?php endforeach ?></tbody></table></div><?php endif ?></section>
<?php endif ?></main><footer>Pejabat Daerah Kuala Terengganu · Equipment Booking<br><small>Development version · Staff identity verification is not connected yet.</small></footer></body></html>
