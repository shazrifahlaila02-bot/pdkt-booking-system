<?php
/**
 * includes/staff_top.php
 * Shared layout (sidebar + styles) for all staff pages.
 * Before including, set:  $judul (page title)  and  $aktif ('dashboard' or 'katalog')
 */
if (!function_exists('inisial')) {
    function inisial($nama) {
        $p = preg_split('/\s+/', trim($nama));
        $i = mb_substr($p[0] ?? '', 0, 1) . (isset($p[1]) ? mb_substr($p[1], 0, 1) : '');
        return mb_strtoupper($i);
    }
}
// Logo: put logo.png in assets/img/ (or img/)
$logoUrl = '';
foreach (['assets/img/logo.png', 'img/logo.png'] as $relLogo) {
    if (file_exists(__DIR__ . '/../' . $relLogo)) { $logoUrl = BASE_URL . '/' . $relLogo; break; }
}
if (!isset($profil)) {
    $uidTop = (int)$_SESSION['user_id'];
    $stTop = mysqli_prepare($dbcon, "SELECT nama, jawatan, bahagian FROM pengguna WHERE id = ?");
    mysqli_stmt_bind_param($stTop, "i", $uidTop);
    mysqli_stmt_execute($stTop);
    $profil = mysqli_fetch_assoc(mysqli_stmt_get_result($stTop)) ?: ['nama' => $_SESSION['nama'], 'jawatan' => '', 'bahagian' => ''];
    mysqli_stmt_close($stTop);
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul) ?> - Sistem Pinjaman Aset PDKT</title>
  <style>
    * { box-sizing:border-box; }
    :root { --biru:#1f4e8c; --biru-gelap:#173d6e; --latar:#f0f3f7; --kelabu:#6b7785; }
    body { margin:0; font-family:Arial, Helvetica, sans-serif; background:var(--latar); color:#1d2733; }
    a { text-decoration:none; }
    @keyframes naik { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }

    .app { display:flex; min-height:100vh; }

    /* ---------- Sidebar ---------- */
    .sisi { width:250px; flex:0 0 250px; background:linear-gradient(180deg,#14325f,#1f4e8c); color:#fff; padding:22px 16px; display:flex; flex-direction:column; position:sticky; top:0; height:100vh; }
    .jenama { display:flex; align-items:center; gap:10px; padding:0 8px 22px; border-bottom:1px solid rgba(255,255,255,.14); color:#fff; }
    .jenama .logo { width:44px; height:50px; flex:0 0 44px; display:flex; align-items:center; justify-content:center; }
    .jenama .logo img { width:100%; height:100%; object-fit:contain; filter:drop-shadow(0 1px 3px rgba(0,0,0,.35)); }
    .jenama .logo.ikon { height:40px; border-radius:10px; background:rgba(255,255,255,.16); }
    .jenama .logo svg { width:22px; height:22px; }
    .jenama b { display:block; font-size:15px; line-height:1.2; }
    .jenama small { font-size:11px; opacity:.75; }
    .menu { margin-top:18px; display:flex; flex-direction:column; gap:4px; }
    .menu a { display:flex; align-items:center; gap:12px; color:#cfdcf0; padding:11px 12px; border-radius:8px; font-size:14px; transition:background .15s; }
    .menu a svg { width:18px; height:18px; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
    .menu a:hover { background:rgba(255,255,255,.1); color:#fff; }
    .menu a.aktif { background:#fff; color:var(--biru); font-weight:bold; }
    .pengguna { margin-top:auto; background:rgba(255,255,255,.1); border-radius:12px; padding:14px; }
    .pengguna .avatar { width:44px; height:44px; border-radius:50%; background:#fff; color:var(--biru); font-weight:bold; display:flex; align-items:center; justify-content:center; margin-bottom:10px; font-size:16px; }
    .pengguna .nama { font-size:14px; font-weight:bold; line-height:1.3; word-break:break-word; }
    .pengguna .peranan { font-size:12px; opacity:.8; margin:2px 0 12px; }
    .pengguna .keluar { display:block; text-align:center; color:#fff; border:1px solid rgba(255,255,255,.55); padding:8px; border-radius:8px; font-size:13px; }
    .pengguna .keluar:hover { background:rgba(255,255,255,.15); }

    /* ---------- Main ---------- */
    .isi { flex:1; min-width:0; padding:26px 30px 50px; }
    .banner { border-radius:16px; color:#fff; padding:30px 32px; background:linear-gradient(135deg,#1f4e8c,#2f7fc1); background-size:cover; background-position:center; position:relative; overflow:hidden; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:18px; animation:naik .5s ease both; }
    .banner::after { content:""; position:absolute; right:-60px; top:-60px; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,.08); }
    .banner .tarikh { font-size:13px; opacity:.85; margin-bottom:6px; }
    .banner h1 { margin:0 0 6px; font-size:26px; line-height:1.25; }
    .banner p { margin:0; font-size:14px; opacity:.92; }
    .btn-baru { position:relative; z-index:1; background:#fff; color:var(--biru); font-weight:bold; padding:12px 20px; border-radius:10px; font-size:14px; box-shadow:0 4px 14px rgba(0,0,0,.18); transition:transform .15s; }
    .btn-baru:hover { transform:translateY(-2px); }

    .amaran { background:#fff4e0; border-left:4px solid #e39a1b; color:#7a4b00; padding:12px 14px; border-radius:8px; margin-top:16px; font-size:14px; }

    .stat { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px; margin-top:20px; }
    .kad { background:#fff; border-radius:14px; padding:18px; box-shadow:0 2px 10px rgba(20,50,95,.07); display:flex; align-items:center; gap:14px; transition:transform .18s, box-shadow .18s; animation:naik .5s ease both; }
    .kad:nth-child(2) { animation-delay:.06s; } .kad:nth-child(3) { animation-delay:.12s; } .kad:nth-child(4) { animation-delay:.18s; }
    .kad:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(20,50,95,.12); }
    .kad .ikon { width:50px; height:50px; border-radius:14px; display:flex; align-items:center; justify-content:center; flex:0 0 50px; }
    .kad .ikon svg { width:26px; height:26px; stroke:#fff; fill:none; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .kad b { display:block; font-size:28px; line-height:1.1; }
    .kad span { font-size:13px; color:var(--kelabu); }
    .i-kuning { background:linear-gradient(135deg,#f3b13c,#e39a1b); } .i-hijau { background:linear-gradient(135deg,#4db577,#3a9d5d); }
    .i-merah { background:linear-gradient(135deg,#e46a6a,#d64545); } .i-kelabu { background:linear-gradient(135deg,#9aa6b3,#7a8795); }

    .grid { display:grid; grid-template-columns:2fr 1fr; gap:18px; margin-top:20px; align-items:start; }
    .panel { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(20,50,95,.07); overflow:hidden; animation:naik .5s ease both; animation-delay:.2s; }
    .panel + .panel { margin-top:18px; }
    .panel h2 { margin:0; padding:16px 20px; font-size:16px; border-bottom:1px solid #eef1f5; display:flex; justify-content:space-between; align-items:center; }
    .panel h2 small { font-weight:normal; font-size:13px; color:var(--kelabu); }

    .jadual { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:14px; }
    th, td { text-align:left; padding:12px 20px; border-bottom:1px solid #eef1f5; white-space:nowrap; }
    th { background:#f7f9fc; font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:var(--kelabu); }
    td.aset { white-space:normal; min-width:180px; }
    .badge { font-size:12px; padding:3px 10px; border-radius:20px; display:inline-block; }
    .badge.menunggu { background:#fff1dd; color:#9a5b00; } .badge.lulus { background:#e6f4ea; color:#1e6b34; }
    .badge.tidak_lulus { background:#fde8e8; color:#9b1c1c; } .badge.dipulangkan { background:#e8edf3; color:#4a5766; }

    .kosong { padding:44px 24px; text-align:center; color:var(--kelabu); font-size:14px; display:flex; flex-direction:column; align-items:center; gap:6px; }
    .kosong svg { width:56px; height:56px; stroke:#b7c4d6; fill:none; stroke-width:1.5; stroke-linecap:round; stroke-linejoin:round; margin-bottom:6px; }
    .kosong b { font-size:16px; color:#35424f; }

    /* Donut */
    .donut-wrap { padding:20px; display:flex; align-items:center; gap:20px; flex-wrap:wrap; justify-content:center; }
    .donut { width:140px; height:140px; border-radius:50%; position:relative; flex:0 0 140px; }
    .donut::after { content:""; position:absolute; inset:22px; background:#fff; border-radius:50%; }
    .donut .tengah { position:absolute; inset:0; z-index:1; display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .donut .tengah b { font-size:26px; line-height:1; } .donut .tengah span { font-size:11px; color:var(--kelabu); margin-top:2px; }
    .legenda { font-size:13px; display:flex; flex-direction:column; gap:7px; }
    .legenda div { display:flex; align-items:center; gap:8px; color:#35424f; }
    .legenda i { width:10px; height:10px; border-radius:50%; display:inline-block; }

    /* Available assets list */
    .senarai-aset { padding:6px 20px 4px; }
    .senarai-aset .item { display:flex; align-items:center; gap:12px; padding:11px 0; border-bottom:1px solid #eef1f5; }
    .senarai-aset .item:last-child { border-bottom:0; }
    .senarai-aset .kotak { width:36px; height:36px; border-radius:10px; background:#e6f4ea; display:flex; align-items:center; justify-content:center; flex:0 0 36px; }
    .senarai-aset .kotak svg { width:18px; height:18px; stroke:#1e6b34; fill:none; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
    .senarai-aset b { display:block; font-size:14px; } .senarai-aset small { color:var(--kelabu); font-size:12px; }
    .pautan-katalog { display:block; margin:6px 20px 18px; text-align:center; background:var(--biru); color:#fff; padding:10px; border-radius:8px; font-size:14px; }
    .pautan-katalog:hover { background:var(--biru-gelap); }

    @media (max-width:900px) {
      .app { flex-direction:column; }
      .sisi { width:100%; flex:none; height:auto; position:static; flex-direction:row; flex-wrap:wrap; align-items:center; gap:10px; padding:14px 16px; }
      .jenama { border:0; padding:0; } .menu { flex-direction:row; margin:0; flex-wrap:wrap; }
      .pengguna { margin:0 0 0 auto; padding:8px 12px; display:flex; align-items:center; gap:10px; }
      .pengguna .avatar { margin:0; width:34px; height:34px; font-size:13px; } .pengguna .nama, .pengguna .peranan { display:none; }
      .pengguna .keluar { padding:6px 12px; }
      .isi { padding:20px 16px 40px; } .grid { grid-template-columns:1fr; }
    }

    /* Page header + filter chips (shared) */
    .kepala { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:14px; margin-bottom:18px; }
    .kepala h1 { margin:0 0 4px; font-size:26px; }
    .kepala p { margin:0; color:var(--kelabu); font-size:14px; }
    .chips { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
    .chip { padding:7px 16px; border-radius:20px; background:#fff; color:#35424f; font-size:13px; border:1px solid #dbe2ea; transition:background .15s; }
    .chip:hover { background:#eaf0f8; }
    .chip.aktif { background:var(--biru); color:#fff; border-color:var(--biru); }
    .chip small { opacity:.75; margin-left:4px; }
<?= $extraCss ?? '' ?>
  </style>
</head>
<body>
<div class="app">

  <!-- ===== Sidebar ===== -->
  <aside class="sisi">
    <a class="jenama" href="<?= BASE_URL ?>/staff/dashboard.php">
      <?php if ($logoUrl): ?>
        <span class="logo"><img src="<?= e($logoUrl) ?>" alt="Logo"></span>
      <?php else: ?>
        <span class="logo ikon"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg></span>
      <?php endif; ?>
      <span><b>Pinjaman Aset</b><small>PDKT</small></span>
    </a>

    <nav class="menu">
      <a class="<?= $aktif === 'dashboard' ? 'aktif' : '' ?>" href="<?= BASE_URL ?>/staff/dashboard.php">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>Dashboard
      </a>
      <a class="<?= $aktif === 'permohonan' ? 'aktif' : '' ?>" href="<?= BASE_URL ?>/staff/permohonan.php">
        <svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="M9 11h6M9 15h4"/></svg>Permohonan Pinjaman
      </a>
      <a class="<?= $aktif === 'katalog' ? 'aktif' : '' ?>" href="<?= BASE_URL ?>/staff/katalog.php">
        <svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/></svg>Katalog Aset
      </a>
    </nav>

    <div class="pengguna">
      <div class="avatar"><?= e(inisial($profil['nama'])) ?></div>
      <div class="nama"><?= e($profil['nama']) ?></div>
      <div class="peranan"><?= e($profil['jawatan'] ?: 'Staff') ?><?= $profil['bahagian'] ? ' · ' . e($profil['bahagian']) : '' ?></div>
      <a class="keluar" href="<?= BASE_URL ?>/logout.php">Log Keluar</a>
    </div>
  </aside>

  <!-- ===== Content ===== -->
  <main class="isi">