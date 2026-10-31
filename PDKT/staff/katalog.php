<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ikon_aset.php';
require_role(['staff']);
require_once __DIR__ . '/../dbconnect.php';

// ---- Filters ----
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
if (!in_array($status, ['tersedia', 'dipinjam', 'rosak'], true)) { $status = ''; }

$where = []; $types = ''; $params = [];
if ($q !== '') {
    $where[] = '(keterangan LIKE ? OR no_siri LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'ss'; $params[] = $like; $params[] = $like;
}
if ($status !== '') {
    $where[] = 'status = ?';
    $types .= 's'; $params[] = $status;
}
$sql = "SELECT * FROM aset" . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY (status = 'tersedia') DESC, keterangan";
$stmt = mysqli_prepare($dbcon, $sql);
if ($params) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$asetList = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ---- Counts for the filter chips ----
$bil = ['semua' => 0, 'tersedia' => 0, 'dipinjam' => 0, 'rosak' => 0];
$rs = mysqli_query($dbcon, "SELECT status, COUNT(*) AS n FROM aset GROUP BY status");
while ($r = mysqli_fetch_assoc($rs)) { $bil[$r['status']] = (int)$r['n']; $bil['semua'] += (int)$r['n']; }

function pautanStatus($st, $q) {
    $qs = [];
    if ($q !== '') { $qs['q'] = $q; }
    if ($st !== '') { $qs['status'] = $st; }
    return BASE_URL . '/staff/katalog.php' . ($qs ? '?' . http_build_query($qs) : '');
}

$statusLabel = ['tersedia' => 'Tersedia', 'dipinjam' => 'Dipinjam', 'rosak' => 'Rosak'];

$extraCss = <<<'CSS'

    /* ---- Katalog page ---- */
    .cari { display:flex; gap:8px; flex-wrap:wrap; }
    .cari input { padding:10px 12px; border:1px solid #cbd3dc; border-radius:8px; font-size:14px; min-width:240px; background:#fff; }
    .cari input:focus { outline:none; border-color:var(--biru); box-shadow:0 0 0 3px rgba(31,78,140,.18); }
    .cari button, .cari a.set { padding:10px 18px; border-radius:8px; font-size:14px; border:1px solid var(--biru); cursor:pointer; }
    .cari button { background:var(--biru); color:#fff; }
    .cari button:hover { background:var(--biru-gelap); }
    .cari a.set { background:#fff; color:var(--biru); }

    .grid-aset { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:18px; }
    .ka { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 2px 10px rgba(20,50,95,.07); display:flex; flex-direction:column; transition:transform .18s, box-shadow .18s; animation:naik .45s ease both; }
    .ka:hover { transform:translateY(-4px); box-shadow:0 10px 24px rgba(20,50,95,.14); }
    .ka .gbr { height:150px; background:linear-gradient(135deg,#e3eaf3,#d3dff0); display:flex; align-items:center; justify-content:center; }
    .ka .gbr img { width:100%; height:100%; object-fit:cover; }
    .ka .gbr svg { width:60px; height:60px; stroke:#1f4e8c; opacity:.5; fill:none; stroke-width:1.5; stroke-linecap:round; stroke-linejoin:round; }
    .ka .teks { padding:14px; display:flex; flex-direction:column; gap:6px; flex:1; }
    .ka h3 { margin:0; font-size:16px; }
    .ka .siri { font-size:12px; color:var(--kelabu); }
    .ka .aksi { margin-top:auto; padding-top:10px; }
    .ka .aksi a, .ka .aksi span { display:block; text-align:center; padding:10px; border-radius:8px; font-size:14px; }
    .ka .aksi a { background:var(--biru); color:#fff; }
    .ka .aksi a:hover { background:var(--biru-gelap); }
    .ka .aksi span { background:#eef0f3; color:#9aa4b0; }
    .badge.tersedia { background:#e6f4ea; color:#1e6b34; align-self:flex-start; }
    .badge.dipinjam { background:#fff1dd; color:#9a5b00; align-self:flex-start; }
    .badge.rosak { background:#fde8e8; color:#9b1c1c; align-self:flex-start; }
    .tiada { background:#fff; border-radius:14px; padding:40px; text-align:center; color:var(--kelabu); box-shadow:0 2px 10px rgba(20,50,95,.07); }
CSS;

$judul = 'Katalog Aset';
$aktif = 'katalog';
require __DIR__ . '/../includes/staff_top.php';
?>

    <div class="kepala">
      <div>
        <h1>Katalog Aset</h1>
        <p>Pilih aset yang tersedia untuk membuat permohonan pinjaman.</p>
      </div>
      <form class="cari" method="get" action="">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <label class="sr-only" for="carian-katalog">Cari aset atau nombor siri</label><input id="carian-katalog" type="search" name="q" placeholder="Cari aset atau no. siri..." value="<?= e($q) ?>">
        <button type="submit">Cari</button>
        <?php if ($q !== ''): ?><a class="set" href="<?= e(pautanStatus($status, '')) ?>">Set Semula</a><?php endif; ?>
      </form>
    </div>

    <div class="chips">
      <a class="chip <?= $status === '' ? 'aktif' : '' ?>" href="<?= e(pautanStatus('', $q)) ?>">Semua<small><?= $bil['semua'] ?></small></a>
      <a class="chip <?= $status === 'tersedia' ? 'aktif' : '' ?>" href="<?= e(pautanStatus('tersedia', $q)) ?>">Tersedia<small><?= $bil['tersedia'] ?></small></a>
      <a class="chip <?= $status === 'dipinjam' ? 'aktif' : '' ?>" href="<?= e(pautanStatus('dipinjam', $q)) ?>">Dipinjam<small><?= $bil['dipinjam'] ?></small></a>
      <a class="chip <?= $status === 'rosak' ? 'aktif' : '' ?>" href="<?= e(pautanStatus('rosak', $q)) ?>">Rosak<small><?= $bil['rosak'] ?></small></a>
    </div>

    <?php if (!$asetList): ?>
      <div class="tiada">Tiada aset dijumpai.</div>
    <?php else: ?>
      <div class="grid-aset">
        <?php foreach ($asetList as $a):
          $st = $a['status'];
          $imgUrl = '';
          if (!empty($a['gambar'])) {
              $f = basename($a['gambar']);
              foreach (['assets/img/aset/', 'img/aset/'] as $dir) {
                  if (file_exists(__DIR__ . '/../' . $dir . $f)) { $imgUrl = BASE_URL . '/' . $dir . $f; break; }
              }
          }
        ?>
          <div class="ka">
            <div class="gbr">
              <?php if ($imgUrl): ?>
                <img src="<?= e($imgUrl) ?>" alt="<?= e($a['keterangan']) ?>">
              <?php else: ?>
                <?= ikon_aset($a['keterangan']) ?>
              <?php endif; ?>
            </div>
            <div class="teks">
              <h3><?= e($a['keterangan']) ?></h3>
              <div class="siri">No. Siri: <?= e($a['no_siri']) ?></div>
              <span class="badge <?= e($st) ?>"><?= e($statusLabel[$st] ?? $st) ?></span>
              <div class="aksi">
                <?php if ($st === 'tersedia'): ?>
                  <a href="<?= BASE_URL ?>/staff/mohon.php?aset=<?= (int)$a['id'] ?>">Mohon Pinjam</a>
                <?php else: ?>
                  <span>Tidak tersedia</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

<?php require __DIR__ . '/../includes/staff_bottom.php'; ?>