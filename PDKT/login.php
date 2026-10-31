<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/dbconnect.php';

// Which portal was chosen on the homepage: staff or admin
$role = $_GET['role'] ?? ($_POST['role'] ?? 'staff');
if (!in_array($role, ['staff', 'admin'], true)) {
    $role = 'staff';
}
$roleLabel = $role === 'admin' ? 'Pentadbir' : 'Kakitangan';

// Already logged in? go straight to the right dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . dashboard_url($_SESSION['peranan']));
    exit;
}

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emel     = trim($_POST['emel'] ?? '');
    $password = $_POST['kata_laluan'] ?? '';
    $token    = $_POST['csrf'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $token)) {
        $error = 'Sesi tidak sah. Sila cuba lagi.';
    } elseif ($emel === '' || $password === '') {
        $error = 'Sila isi emel dan kata laluan.';
    } else {
        $stmt = mysqli_prepare($dbcon, "SELECT id, nama, kata_laluan, peranan FROM pengguna WHERE emel = ?");
        mysqli_stmt_bind_param($stmt, "s", $emel);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['kata_laluan'])) {
            if ($user['peranan'] !== $role) {
                // Correct password but wrong portal
                $error = 'Akaun ini bukan akaun ' . $roleLabel . '. Sila pilih portal yang betul.';
            } else {
                session_regenerate_id(true);               // prevent session fixation
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama']    = $user['nama'];
                $_SESSION['peranan'] = $user['peranan'];
                $_SESSION['csrf']    = bin2hex(random_bytes(32));
                header('Location: ' . dashboard_url($user['peranan']));
                exit;
            }
        } else {
            // Same message whether email or password is wrong
            $error = 'Emel atau kata laluan tidak betul.';
        }
    }
}

// Look & feel: accent colour per portal + photo on the left panel
$accent     = $role === 'admin' ? '#7a2e8c' : '#1f4e8c';
$accentDark = $role === 'admin' ? '#632472' : '#173d6e';
$panelStyle = '';
foreach (['assets/img/pdkt.jpg', 'img/pdkt.jpg', 'assets/img/hero.jpg', 'img/hero.jpg'] as $rel) {
    if (file_exists(__DIR__ . '/' . $rel)) {
        $panelStyle = "background-image: linear-gradient(180deg, rgba(15,40,80,.25) 0%, rgba(15,40,80,.88) 100%), url('" . BASE_URL . "/" . $rel . "');";
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log Masuk <?= e($roleLabel) ?> - Sistem Pinjaman Aset PDKT</title>
  <style>
    * { box-sizing:border-box; }
    body { margin:0; min-height:100vh; font-family: Arial, Helvetica, sans-serif; color:#1d2733;
           background:linear-gradient(135deg,#e8eef7,#d9e4f3); display:flex; align-items:center; justify-content:center; padding:24px; }
    .balik { position:fixed; top:18px; left:20px; color:#1f4e8c; text-decoration:none; font-size:14px; background:#fff; padding:8px 14px; border-radius:20px; box-shadow:0 1px 6px rgba(0,0,0,.12); }
    .balik:hover { background:#eef3fa; }

    .wrap { width:100%; max-width:880px; display:flex; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 12px 40px rgba(20,50,95,.22); }

    /* Left photo panel */
    .kiri { flex:1 1 45%; min-height:480px; color:#fff; padding:36px 32px; display:flex; flex-direction:column; justify-content:flex-end; position:relative;
            background:linear-gradient(135deg,var(--acc),#2f7fc1); background-size:cover; background-position:center; }
    .kiri .logo { width:52px; height:52px; border-radius:12px; background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center; margin-bottom:14px; backdrop-filter:blur(2px); }
    .kiri .logo svg { width:28px; height:28px; }
    .kiri h2 { margin:0 0 6px; font-size:24px; line-height:1.25; }
    .kiri p { margin:0 0 18px; font-size:14px; opacity:.92; }
    .kiri ul { list-style:none; padding:0; margin:0; font-size:14px; }
    .kiri li { padding:4px 0 4px 24px; position:relative; opacity:.95; }
    .kiri li::before { content:"\2713"; position:absolute; left:0; font-weight:bold; }

    /* Right form panel */
    .kanan { flex:1 1 55%; padding:40px 38px; display:flex; flex-direction:column; justify-content:center; }
    .tag { align-self:flex-start; background:var(--acc); color:#fff; font-size:12px; padding:4px 12px; border-radius:20px; margin-bottom:12px; }
    h1 { font-size:26px; margin:0 0 4px; }
    p.sub { color:#6b7785; font-size:14px; margin:0 0 22px; }

    label { display:block; font-size:13px; font-weight:bold; margin:14px 0 6px; color:#35424f; }
    .medan { position:relative; }
    .medan svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); width:18px; height:18px; stroke:#8a96a3; fill:none; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; pointer-events:none; }
    .medan input { width:100%; padding:12px 14px 12px 40px; border:1px solid #cbd3dc; border-radius:8px; font-size:15px; background:#fafbfc; transition:border-color .15s, box-shadow .15s; }
    .medan input:focus { outline:none; border-color:var(--acc); background:#fff; box-shadow:0 0 0 3px color-mix(in srgb, var(--acc) 22%, transparent); }
    .medan .tunjuk { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:0; color:var(--acc); font-size:12px; cursor:pointer; padding:6px 8px; margin:0; width:auto; }

    .btn-masuk { width:100%; margin-top:22px; padding:13px; background:var(--acc); color:#fff; border:0; border-radius:8px; font-size:16px; font-weight:bold; cursor:pointer; transition:background .15s, transform .05s; }
    .btn-masuk:hover { background:var(--acc-d); }
    .btn-masuk:active { transform:translateY(1px); }

    .error, .success { padding:11px 12px; border-radius:8px; font-size:14px; margin-bottom:6px; }
    .error { background:#fde8e8; color:#9b1c1c; border-left:4px solid #d64545; }
    .success { background:#e6f4ea; color:#1e6b34; border-left:4px solid #3a9d5d; }

    .alt { text-align:center; font-size:14px; margin:16px 0 0; color:#4a5766; }
    .alt a { color:var(--acc); font-weight:bold; text-decoration:none; }
    .alt a:hover { text-decoration:underline; }

    @media (max-width:760px) {
      .kiri { display:none; }
      .kanan { padding:32px 24px; }
      .balik { position:static; align-self:flex-start; margin-bottom:12px; }
      body { flex-direction:column; justify-content:center; }
    }
  </style>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tema.css">
<script src="<?= BASE_URL ?>/assets/js/antara-muka.js" defer></script>
</head>
<body class="halaman-akaun" style="--acc:<?= $accent ?>; --acc-d:<?= $accentDark ?>;">

  <a class="balik" href="index.php">&larr; Laman Utama</a>

  <div class="wrap">
    <!-- Left: photo + branding -->
    <div class="kiri" style="<?= $panelStyle ?>">
      <span class="tajuk-kecil cerah">PEJABAT DAERAH KUALA TERENGGANU</span><div class="logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg>
      </div>
      <h2>Urus aset.<br><em>Permudahkan kerja.</em></h2>
      <p>Pejabat Daerah Kuala Terengganu</p>
      <ul>
        <?php if ($role === 'admin'): ?>
          <li>Urus aset dan pengguna</li>
          <li>Luluskan atau tolak permohonan</li>
          <li>Rekod pemulangan aset</li>
        <?php else: ?>
          <li>Semak aset yang tersedia</li>
          <li>Hantar permohonan pinjaman</li>
          <li>Jejak status kelulusan</li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Right: form -->
    <div class="kanan" id="kandungan">
      <span class="tag">Portal <?= e($roleLabel) ?></span>
      <h1>Log Masuk <?= e($roleLabel) ?></h1>
      <p class="sub">Masukkan emel dan kata laluan anda untuk meneruskan.</p>

      <?php if ($success): ?>
        <div class="success" role="status"><?= e($success) ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="role" value="<?= e($role) ?>">

        <label for="emel">Emel</label>
        <div class="medan">
          <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
          <input type="email" id="emel" name="emel" value="<?= e($_POST['emel'] ?? '') ?>" placeholder="nama@contoh.com" autocomplete="username" required autofocus>
        </div>

        <label for="kata_laluan">Kata Laluan</label>
        <div class="medan">
          <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
          <input class="ada-togol" type="password" id="kata_laluan" name="kata_laluan" placeholder="Masukkan kata laluan" autocomplete="current-password" required>
          <button type="button" class="tunjuk" id="tunjuk" aria-label="Tunjuk atau sembunyi kata laluan">Tunjuk</button>
        </div>

        <button type="submit" class="btn-masuk">Log Masuk</button>
      </form>

      <?php if ($role === 'staff'): ?>
        <p class="alt">Belum ada akaun? <a href="register.php">Daftar di sini</a></p>
        <p class="alt">Anda pentadbir? <a href="login.php?role=admin">Log masuk Pentadbir</a></p>
      <?php else: ?>
        <p class="alt">Anda kakitangan? <a href="login.php?role=staff">Log masuk Kakitangan</a></p>
      <?php endif; ?>
    </div>
  </div>

  <script>
    var t = document.getElementById('tunjuk'), p = document.getElementById('kata_laluan');
    t.addEventListener('click', function () {
      var show = p.type === 'password';
      p.type = show ? 'text' : 'password';
      t.textContent = show ? 'Sembunyi' : 'Tunjuk';
    });
  </script>
</body>
</html>