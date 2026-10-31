<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ikon_aset.php';
require_once __DIR__ . '/dbconnect.php';

$loggedIn = !empty($_SESSION['user_id']);
$peranan  = $_SESSION['peranan'] ?? '';

// ---- Search + catalog ----
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = mysqli_prepare($dbcon,
        "SELECT * FROM aset WHERE keterangan LIKE ? OR no_siri LIKE ?
         ORDER BY (status = 'tersedia') DESC, keterangan");
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
} else {
    $stmt = mysqli_prepare($dbcon,
        "SELECT * FROM aset ORDER BY (status = 'tersedia') DESC, keterangan");
}
mysqli_stmt_execute($stmt);
$asetList = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ---- Stats ----
$stat = mysqli_fetch_assoc(mysqli_query($dbcon,
    "SELECT COUNT(*) AS jumlah, SUM(status='tersedia') AS tersedia, SUM(status='dipinjam') AS dipinjam FROM aset"));
$jumlah   = (int)($stat['jumlah'] ?? 0);
$tersedia = (int)($stat['tersedia'] ?? 0);
$dipinjam = (int)($stat['dipinjam'] ?? 0);

// Hero background photo: looks in assets/img/ and img/ (pdkt.jpg first, then hero.jpg)
$heroUrl = '';
foreach (['assets/img/pdkt.jpg', 'img/pdkt.jpg', 'assets/img/hero.jpg', 'img/hero.jpg'] as $rel) {
    if (file_exists(__DIR__ . '/' . $rel)) { $heroUrl = BASE_URL . '/' . $rel; break; }
}
$heroStyle = $heroUrl !== ''
    ? "background-image: linear-gradient(90deg, rgba(15,40,80,.90) 0%, rgba(15,40,80,.60) 55%, rgba(15,40,80,.25) 100%), url('" . $heroUrl . "'); background-size:cover; background-position:center;"
    : "";

$statusLabel = ['tersedia' => 'Tersedia', 'dipinjam' => 'Dipinjam', 'rosak' => 'Rosak'];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistem Pinjaman Aset - Pejabat Daerah Kuala Terengganu</title>
  <style>
    :root { --biru:#1f4e8c; --biru-gelap:#173d6e; --latar:#f0f3f7; --teks:#1d2733; --kelabu:#6b7785; }
    * { box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body { margin:0; font-family: Arial, Helvetica, sans-serif; color:var(--teks); background:var(--latar); }
    a { text-decoration:none; }

    /* Navbar */
    nav { position:sticky; top:0; z-index:10; background:#fff; box-shadow:0 1px 6px rgba(0,0,0,.08); }
    .nav-in { max-width:1100px; margin:0 auto; padding:12px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .brand { font-weight:bold; color:var(--biru); font-size:17px; }
    .brand small { display:block; font-weight:normal; color:var(--kelabu); font-size:12px; }
    .menu { display:flex; align-items:center; gap:18px; flex-wrap:wrap; }
    .menu a.link { color:var(--teks); font-size:15px; }
    .menu a.link:hover { color:var(--biru); }
    .btn { display:inline-block; padding:9px 18px; border-radius:6px; font-size:14px; border:1px solid var(--biru); color:var(--biru); background:#fff; cursor:pointer; }
    .btn:hover { background:#eaf0f8; }
    .btn.utama { background:var(--biru); color:#fff; }
    .btn.utama:hover { background:var(--biru-gelap); }
    .btn.admin { background:#7a2e8c; border-color:#7a2e8c; color:#fff; }
    .btn.admin:hover { background:#632472; }
    .btn.putih { background:#fff; color:var(--biru); border-color:#fff; }
    .btn.garis { background:transparent; color:#fff; border-color:#fff; }
    .btn.garis:hover { background:rgba(255,255,255,.15); }

    /* Hero */
    .hero { background:linear-gradient(135deg,#1f4e8c,#2f7fc1); color:#fff; padding:100px 20px 120px; <?= $heroStyle ?> }
    .hero-in { max-width:1100px; margin:0 auto; display:flex; align-items:center; gap:40px; flex-wrap:wrap; min-height:300px; }
    .hero-teks { flex:1 1 380px; max-width:620px; text-shadow:0 1px 6px rgba(0,0,0,.35); }
    .hero h1 { font-size:36px; margin:0 0 12px; line-height:1.2; }
    .hero p { font-size:17px; margin:0 0 24px; opacity:.95; max-width:520px; }
    .hero .btns { display:flex; gap:12px; flex-wrap:wrap; }
    .hero-gambar { flex:0 1 300px; text-align:center; margin-left:auto; }
    .hero-gambar svg { max-width:300px; width:100%; height:auto; opacity:.95; }

    /* Stats */
    .stats { max-width:1100px; margin:-112px auto 0; padding:0 20px; display:grid; grid-template-columns:repeat(3,1fr); gap:14px; position:relative; }
    .stat { background:#fff; border-radius:10px; padding:18px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,.08); }
    .stat b { display:block; font-size:28px; color:var(--biru); }
    .stat span { font-size:13px; color:var(--kelabu); }

    section { max-width:1100px; margin:0 auto; padding:50px 20px 10px; }
    h2 { font-size:24px; margin:0 0 6px; }
    .sub { color:var(--kelabu); margin:0 0 24px; font-size:15px; }

    /* Steps */
    .steps { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
    .step { background:#fff; border-radius:10px; padding:20px; box-shadow:0 1px 6px rgba(0,0,0,.06); }
    .step .no { width:34px; height:34px; border-radius:50%; background:var(--biru); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold; margin-bottom:10px; }
    .step h3 { margin:0 0 6px; font-size:16px; }
    .step p { margin:0; font-size:14px; color:var(--kelabu); }

    /* Catalog */
    .cari { display:flex; gap:8px; margin-bottom:22px; flex-wrap:wrap; }
    .cari input { flex:1 1 240px; padding:10px 12px; border:1px solid #cbd3dc; border-radius:6px; font-size:14px; }
    .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:18px; }
    .kad { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 1px 8px rgba(0,0,0,.08); display:flex; flex-direction:column; }
    .kad .img { height:150px; background:#e3eaf3; display:flex; align-items:center; justify-content:center; }
    .kad .img img { width:100%; height:100%; object-fit:cover; }
    .kad .img svg { width:60px; height:60px; opacity:.55; }
    .kad .isi { padding:14px; display:flex; flex-direction:column; gap:6px; flex:1; }
    .kad h3 { margin:0; font-size:16px; }
    .kad .siri { font-size:12px; color:var(--kelabu); }
    .badge { align-self:flex-start; font-size:12px; padding:3px 10px; border-radius:20px; }
    .badge.tersedia { background:#e6f4ea; color:#1e6b34; }
    .badge.dipinjam { background:#fff1dd; color:#9a5b00; }
    .badge.rosak { background:#fde8e8; color:#9b1c1c; }
    .kad .aksi { margin-top:auto; padding-top:10px; }
    .kad .aksi .btn { width:100%; text-align:center; }
    .btn.mati { background:#eef0f3; color:#9aa4b0; border-color:#eef0f3; cursor:not-allowed; }
    .kosong { background:#fff; border-radius:10px; padding:30px; text-align:center; color:var(--kelabu); }

    footer { margin-top:60px; background:#14325f; color:#cfd9e8; padding:26px 20px; text-align:center; font-size:14px; }

    @media (max-width:600px) {
      .hero h1 { font-size:28px; }
      .stats { grid-template-columns:1fr; margin-top:-20px; }
    }
  </style>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tema.css">
<script src="<?= BASE_URL ?>/assets/js/antara-muka.js" defer></script>
</head>
<body class="halaman-utama"><a class="langkau" href="#kandungan">Langkau ke kandungan</a>

<nav>
  <div class="nav-in">
    <a href="<?= BASE_URL ?>/index.php" class="brand"><span class="lencana-jenama" aria-hidden="true">P.</span><span>Sistem Pinjaman Aset<small>Pejabat Daerah Kuala Terengganu</small></span></a>
    <div class="menu">
      <a class="link" href="#cara">Cara Pinjam</a>
      <a class="link" href="#katalog">Katalog Aset</a>
      <?php if ($loggedIn): ?>
        <a class="btn utama" href="<?= e(dashboard_url($peranan)) ?>">Papan Pemuka</a>
        <a class="btn" href="<?= BASE_URL ?>/logout.php">Log Keluar</a>
      <?php else: ?>
        <a class="btn utama" href="<?= BASE_URL ?>/login.php?role=staff">Log Masuk Kakitangan</a>
        <a class="btn admin" href="<?= BASE_URL ?>/login.php?role=admin">Log Masuk Pentadbir</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<header class="hero" id="kandungan">
  <div class="hero-in">
    <div class="hero-teks">
      <span class="label-kecil"><i></i> PORTAL PINJAMAN ASET PDKT</span>
      <h1>Keperluan kerja anda.<br><em>Kini lebih teratur.</em></h1>
      <p>Komputer riba, telefon dan kamera untuk urusan rasmi. Semak ketersediaan, mohon pinjaman dan jejak permohonan dalam satu tempat.</p>
      <div class="btns">
        <a class="btn putih" href="<?= $loggedIn ? e(dashboard_url($peranan)) : BASE_URL . '/login.php?role=staff' ?>"><?= $loggedIn ? 'Buka Papan Pemuka' : 'Mula Memohon' ?> <span aria-hidden="true">→</span></a>
        <a class="btn garis" href="#katalog">Terokai Katalog <span aria-hidden="true">↓</span></a>
      </div>
      <div class="hero-nota"><span aria-hidden="true">✓</span> Untuk kakitangan Pejabat Daerah Kuala Terengganu</div>
    </div>
    <div class="hero-visual">
      <div class="bingkai-foto"><img src="<?= BASE_URL ?>/img/pdkt.jpg" alt="Bangunan Pejabat Daerah Kuala Terengganu"><div class="foto-kapsyen"><small>PEJABAT DAERAH</small><strong>Kuala Terengganu</strong></div></div>
      <div class="kad-terapung"><span class="bulat-semak" aria-hidden="true">✓</span><div><strong><?= $tersedia ?> aset tersedia</strong><small>Sedia untuk permohonan anda</small></div></div>
      <div class="label-aset"><span>Komputer riba</span><span>Telefon</span><span>Kamera</span></div>
      <span class="hiasan-bintang" aria-hidden="true"><svg viewBox="0 0 60 60" fill="none" stroke="currentColor" stroke-width="3"><path d="M30 3v54M3 30h54M11 11l38 38M11 49l38-38"/></svg></span>
    </div>
  </div>
</header>

<div class="stats">
  <div class="stat"><b><?= $jumlah ?></b><span>Jumlah Aset</span></div>
  <div class="stat"><b><?= $tersedia ?></b><span>Tersedia</span></div>
  <div class="stat"><b><?= $dipinjam ?></b><span>Sedang Dipinjam</span></div>
</div>

<section id="cara">
  <span class="tajuk-kecil">RINGKAS &amp; TERATUR</span><h2>Dari permohonan hingga pemulangan.</h2>
  <p class="sub">Empat langkah mudah mengikut proses borang KEW.PA-9.</p>
  <div class="steps">
    <div class="step"><div class="no">1</div><h3>Daftar &amp; Log Masuk</h3><p>Daftar akaun menggunakan emel dan maklumat jawatan anda.</p></div>
    <div class="step"><div class="no">2</div><h3>Pilih Aset</h3><p>Semak katalog dan pilih aset yang berstatus tersedia.</p></div>
    <div class="step"><div class="no">3</div><h3>Hantar Permohonan</h3><p>Isi tujuan, tempat digunakan, tarikh pinjam dan tarikh pulang.</p></div>
    <div class="step"><div class="no">4</div><h3>Kelulusan &amp; Pemulangan</h3><p>Pentadbir menyemak permohonan. Pulangkan aset pada tarikh dijangka.</p></div>
  </div>
</section>

<section id="katalog">
  <span class="tajuk-kecil">PILIH KEPERLUAN ANDA</span><h2>Katalog Aset</h2>
  <p class="sub">Senarai aset alih yang boleh dipinjam.</p>

  <form class="cari" method="get" action="#katalog">
    <label class="sr-only" for="carian-utama">Cari aset atau nombor siri</label><input id="carian-utama" type="search" name="q" placeholder="Cari aset atau no. siri..." value="<?= e($q) ?>">
    <button class="btn utama" type="submit">Cari</button>
    <?php if ($q !== ''): ?><a class="btn" href="<?= BASE_URL ?>/index.php#katalog">Set Semula</a><?php endif; ?>
  </form>

  <?php if (!$asetList): ?>
    <div class="kosong">Tiada aset dijumpai.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($asetList as $a):
        $st = $a['status'];
        $imgFile = !empty($a['gambar']) ? basename($a['gambar']) : '';
        $hasImg  = $imgFile !== '' && file_exists(__DIR__ . '/assets/img/aset/' . $imgFile);
      ?>
        <div class="kad">
          <div class="img">
            <?php if ($hasImg): ?>
              <img src="<?= BASE_URL ?>/assets/img/aset/<?= e($imgFile) ?>" alt="<?= e($a['keterangan']) ?>">
            <?php else: ?>
              <?= ikon_aset($a['keterangan']) ?>
            <?php endif; ?>
          </div>
          <div class="isi">
            <h3><?= e($a['keterangan']) ?></h3>
            <div class="siri">No. Siri: <?= e($a['no_siri']) ?></div>
            <span class="badge <?= e($st) ?>"><?= e($statusLabel[$st] ?? $st) ?></span>
            <div class="aksi">
              <?php if ($st !== 'tersedia'): ?>
                <span class="btn mati" style="display:block;text-align:center">Tidak tersedia</span>
              <?php elseif (!$loggedIn): ?>
                <a class="btn utama" href="<?= BASE_URL ?>/login.php?role=staff">Log masuk untuk mohon</a>
              <?php elseif ($peranan === 'staff'): ?>
                <a class="btn utama" href="<?= BASE_URL ?>/staff/mohon.php?aset=<?= (int)$a['id'] ?>">Mohon Pinjam</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<footer>
  &copy; <?= date('Y') ?> Pejabat Daerah Kuala Terengganu &middot; Sistem Pinjaman Aset Alih (KEW.PA-9)
</footer>

</body>
</html>