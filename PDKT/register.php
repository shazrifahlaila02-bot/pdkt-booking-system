<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/dbconnect.php';

// Already logged in? no need to register
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . dashboard_url($_SESSION['peranan']));
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$old = ['nama' => '', 'jawatan' => '', 'bahagian' => '', 'emel' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['nama']     = trim($_POST['nama'] ?? '');
    $old['jawatan']  = trim($_POST['jawatan'] ?? '');
    $old['bahagian'] = trim($_POST['bahagian'] ?? '');
    $old['emel']     = trim($_POST['emel'] ?? '');
    $password        = $_POST['kata_laluan'] ?? '';
    $confirm         = $_POST['sahkan_kata_laluan'] ?? '';
    $token           = $_POST['csrf'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $token)) {
        $errors[] = 'Sesi tidak sah. Sila cuba lagi.';
    }
    if ($old['nama'] === '' || mb_strlen($old['nama']) > 100) {
        $errors[] = 'Sila isi nama (maksimum 100 aksara).';
    }
    if ($old['jawatan'] === '' || mb_strlen($old['jawatan']) > 100) {
        $errors[] = 'Sila isi jawatan.';
    }
    if ($old['bahagian'] === '' || mb_strlen($old['bahagian']) > 100) {
        $errors[] = 'Sila isi bahagian.';
    }
    if (!filter_var($old['emel'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['emel']) > 100) {
        $errors[] = 'Sila isi emel yang sah.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Kata laluan mesti sekurang-kurangnya 8 aksara.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Kata laluan dan pengesahan tidak sama.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $peranan = 'staff';     // public sign-up is ALWAYS staff; never taken from the form

        try {
            $stmt = mysqli_prepare($dbcon,
                "INSERT INTO pengguna (nama, jawatan, bahagian, emel, kata_laluan, peranan) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssss",
                $old['nama'], $old['jawatan'], $old['bahagian'], $old['emel'], $hash, $peranan);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $_SESSION['flash'] = 'Pendaftaran berjaya. Sila log masuk.';
            header('Location: ' . BASE_URL . '/login.php?role=staff');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {          // duplicate email
                $errors[] = 'Emel ini sudah didaftarkan.';
            } else {
                error_log('Register failed: ' . $ex->getMessage());
                $errors[] = 'Pendaftaran gagal. Sila cuba lagi.';
            }
        }
    }
}

// Left panel photo (same picture as the homepage)
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
  <title>Daftar Akaun - Sistem Pinjaman Aset PDKT</title>
  <style>
    * { box-sizing:border-box; }
    :root { --acc:#1f4e8c; --acc-d:#173d6e; }
    body { margin:0; min-height:100vh; font-family: Arial, Helvetica, sans-serif; color:#1d2733;
           background:linear-gradient(135deg,#e8eef7,#d9e4f3); display:flex; align-items:center; justify-content:center; padding:24px; }
    .balik { position:fixed; top:18px; left:20px; color:#1f4e8c; text-decoration:none; font-size:14px; background:#fff; padding:8px 14px; border-radius:20px; box-shadow:0 1px 6px rgba(0,0,0,.12); z-index:5; }
    .balik:hover { background:#eef3fa; }

    .wrap { width:100%; max-width:960px; display:flex; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 12px 40px rgba(20,50,95,.22); margin:40px 0; }

    /* Left photo panel */
    .kiri { flex:1 1 38%; min-height:560px; color:#fff; padding:36px 30px; display:flex; flex-direction:column; justify-content:flex-end;
            background:linear-gradient(135deg,var(--acc),#2f7fc1); background-size:cover; background-position:center; }
    .kiri .logo { width:52px; height:52px; border-radius:12px; background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center; margin-bottom:14px; }
    .kiri .logo svg { width:28px; height:28px; }
    .kiri h2 { margin:0 0 6px; font-size:24px; line-height:1.25; }
    .kiri p { margin:0 0 18px; font-size:14px; opacity:.92; }
    .kiri ul { list-style:none; padding:0; margin:0; font-size:14px; }
    .kiri li { padding:4px 0 4px 24px; position:relative; opacity:.95; }
    .kiri li::before { content:"\2713"; position:absolute; left:0; font-weight:bold; }

    /* Right form panel */
    .kanan { flex:1 1 62%; padding:36px 38px; display:flex; flex-direction:column; justify-content:center; }
    .tag { align-self:flex-start; background:var(--acc); color:#fff; font-size:12px; padding:4px 12px; border-radius:20px; margin-bottom:10px; }
    h1 { font-size:26px; margin:0 0 4px; }
    p.sub { color:#6b7785; font-size:14px; margin:0 0 14px; }

    label { display:block; font-size:13px; font-weight:bold; margin:14px 0 6px; color:#35424f; }
    .baris { display:grid; grid-template-columns:1fr 1fr; gap:0 14px; }
    .medan { position:relative; }
    .medan svg.ik { position:absolute; left:12px; top:50%; transform:translateY(-50%); width:18px; height:18px; stroke:#8a96a3; fill:none; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; pointer-events:none; }
    .medan input { width:100%; padding:12px 14px 12px 40px; border:1px solid #cbd3dc; border-radius:8px; font-size:15px; background:#fafbfc; transition:border-color .15s, box-shadow .15s; }
    .medan input:focus { outline:none; border-color:var(--acc); background:#fff; box-shadow:0 0 0 3px rgba(31,78,140,.2); }
    .tunjuk-semua { background:none; border:0; color:var(--acc); font-size:13px; cursor:pointer; padding:0; margin-top:8px; text-align:left; align-self:flex-start; }
    .hint { font-size:12px; color:#8a96a3; margin-top:4px; }
    .padan { font-size:12px; margin-top:4px; min-height:15px; }
    .padan.ok { color:#1e6b34; } .padan.tidak { color:#9b1c1c; }

    .btn-daftar { width:100%; margin-top:20px; padding:13px; background:var(--acc); color:#fff; border:0; border-radius:8px; font-size:16px; font-weight:bold; cursor:pointer; transition:background .15s, transform .05s; }
    .btn-daftar:hover { background:var(--acc-d); }
    .btn-daftar:active { transform:translateY(1px); }

    .error { background:#fde8e8; color:#9b1c1c; padding:11px 12px 11px 28px; border-radius:8px; font-size:14px; margin-bottom:4px; border-left:4px solid #d64545; }
    .error ul { margin:0; padding:0; }

    .alt { text-align:center; font-size:14px; margin:16px 0 0; color:#4a5766; }
    .alt a { color:var(--acc); font-weight:bold; text-decoration:none; }
    .alt a:hover { text-decoration:underline; }

    @media (max-width:800px) {
      .kiri { display:none; }
      .kanan { padding:30px 22px; }
      .baris { grid-template-columns:1fr; }
      .balik { position:static; align-self:flex-start; margin-bottom:12px; }
      body { flex-direction:column; justify-content:flex-start; }
      .wrap { margin:0; }
    }
  </style>
</head>
<body>

  <a class="balik" href="index.php">&larr; Laman Utama</a>

  <div class="wrap">
    <!-- Left: photo + branding -->
    <div class="kiri" style="<?= $panelStyle ?>">
      <div class="logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg>
      </div>
      <h2>Sertai Sistem Pinjaman Aset</h2>
      <p>Pejabat Daerah Kuala Terengganu</p>
      <ul>
        <li>Semak aset yang tersedia</li>
        <li>Hantar permohonan secara dalam talian</li>
        <li>Jejak status kelulusan anda</li>
      </ul>
    </div>

    <!-- Right: form -->
    <div class="kanan">
      <span class="tag">Akaun Staff</span>
      <h1>Daftar Akaun</h1>
      <p class="sub">Isi maklumat di bawah untuk mencipta akaun staff.</p>

      <?php if ($errors): ?>
        <div class="error"><ul>
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <form method="post" action="">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

        <label for="nama">Nama Penuh</label>
        <div class="medan">
          <svg class="ik" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
          <input type="text" id="nama" name="nama" value="<?= e($old['nama']) ?>" maxlength="100" placeholder="Nama seperti dalam kad pengenalan" autocomplete="name" required autofocus>
        </div>

        <div class="baris">
          <div>
            <label for="jawatan">Jawatan</label>
            <div class="medan">
              <svg class="ik" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
              <input type="text" id="jawatan" name="jawatan" value="<?= e($old['jawatan']) ?>" maxlength="100" placeholder="Cth: Pembantu Tadbir" required>
            </div>
          </div>
          <div>
            <label for="bahagian">Bahagian</label>
            <div class="medan">
              <svg class="ik" viewBox="0 0 24 24"><path d="M4 21V5l8-2v18"/><path d="M12 9h8v12"/><path d="M8 9h1M8 13h1M8 17h1M16 13h1M16 17h1"/></svg>
              <input type="text" id="bahagian" name="bahagian" value="<?= e($old['bahagian']) ?>" maxlength="100" placeholder="Cth: Pentadbiran" required>
            </div>
          </div>
        </div>

        <label for="emel">Emel</label>
        <div class="medan">
          <svg class="ik" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
          <input type="email" id="emel" name="emel" value="<?= e($old['emel']) ?>" maxlength="100" placeholder="nama@contoh.com" autocomplete="email" required>
        </div>

        <div class="baris">
          <div>
            <label for="kata_laluan">Kata Laluan</label>
            <div class="medan">
              <svg class="ik" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
              <input type="password" id="kata_laluan" name="kata_laluan" minlength="8" placeholder="Min. 8 aksara" autocomplete="new-password" required>
            </div>
          </div>
          <div>
            <label for="sahkan_kata_laluan">Sahkan Kata Laluan</label>
            <div class="medan">
              <svg class="ik" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
              <input type="password" id="sahkan_kata_laluan" name="sahkan_kata_laluan" minlength="8" placeholder="Taip semula" autocomplete="new-password" required>
            </div>
          </div>
        </div>
        <div class="padan" id="padan"></div>
        <button type="button" class="tunjuk-semua" id="tunjuk">Tunjuk kata laluan</button>

        <button type="submit" class="btn-daftar">Daftar</button>
      </form>

      <p class="alt">Sudah ada akaun? <a href="login.php?role=staff">Log masuk</a></p>
    </div>
  </div>

  <script>
    var k1 = document.getElementById('kata_laluan'),
        k2 = document.getElementById('sahkan_kata_laluan'),
        pd = document.getElementById('padan'),
        tb = document.getElementById('tunjuk');

    function semak() {
      if (!k2.value) { pd.textContent = ''; pd.className = 'padan'; return; }
      if (k1.value === k2.value) { pd.textContent = 'Kata laluan sepadan'; pd.className = 'padan ok'; }
      else { pd.textContent = 'Kata laluan tidak sepadan'; pd.className = 'padan tidak'; }
    }
    k1.addEventListener('input', semak);
    k2.addEventListener('input', semak);

    tb.addEventListener('click', function () {
      var show = k1.type === 'password';
      k1.type = k2.type = show ? 'text' : 'password';
      tb.textContent = show ? 'Sembunyi kata laluan' : 'Tunjuk kata laluan';
    });
  </script>
</body>
</html>