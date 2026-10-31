<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['staff']);
require_once __DIR__ . '/../dbconnect.php';

$uid = (int)$_SESSION['user_id'];

// ---- Profile ----
$stmt = mysqli_prepare($dbcon, "SELECT nama, jawatan, bahagian FROM pengguna WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$profil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: ['nama' => $_SESSION['nama'], 'jawatan' => '', 'bahagian' => ''];
mysqli_stmt_close($stmt);

// ---- My request counts ----
$stmt = mysqli_prepare($dbcon,
    "SELECT SUM(status='menunggu') AS menunggu,
            SUM(status='lulus') AS lulus,
            SUM(status='tidak_lulus') AS tolak,
            SUM(status='dipulangkan') AS pulang,
            SUM(status='lulus' AND tarikh_dijangka_pulang < CURDATE()) AS lewat
     FROM permohonan WHERE pemohon_id = ?");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$c = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$menunggu = (int)($c['menunggu'] ?? 0);
$lulus    = (int)($c['lulus'] ?? 0);
$tolak    = (int)($c['tolak'] ?? 0);
$pulang   = (int)($c['pulang'] ?? 0);
$lewat    = (int)($c['lewat'] ?? 0);
$jumlah   = $menunggu + $lulus + $tolak + $pulang;

// ---- Latest requests (with the assets in each) ----
$stmt = mysqli_prepare($dbcon,
    "SELECT p.no_permohonan, p.tarikh_pinjam, p.tarikh_dijangka_pulang, p.status,
            GROUP_CONCAT(a.keterangan SEPARATOR ', ') AS aset
     FROM permohonan p
     LEFT JOIN permohonan_aset pa ON pa.permohonan_id = p.id
     LEFT JOIN aset a ON a.id = pa.aset_id
     WHERE p.pemohon_id = ?
     GROUP BY p.id
     ORDER BY p.created_at DESC
     LIMIT 8");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$senarai = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ---- Available assets (count + a few examples) ----
$row = mysqli_fetch_assoc(mysqli_query($dbcon, "SELECT COUNT(*) AS n FROM aset WHERE status = 'tersedia'"));
$tersedia = (int)($row['n'] ?? 0);
$contoh = mysqli_fetch_all(mysqli_query($dbcon,
    "SELECT keterangan, no_siri FROM aset WHERE status = 'tersedia' ORDER BY keterangan LIMIT 4"), MYSQLI_ASSOC);

// ---- Helpers ----
$label = ['menunggu' => 'Menunggu', 'lulus' => 'Diluluskan', 'tidak_lulus' => 'Ditolak', 'dipulangkan' => 'Dipulangkan'];
function tarikh($d) { return $d ? date('d/m/Y', strtotime($d)) : '-'; }

// Greeting by time of day + today's date in Malay
$jam = (int)date('G');
$ucap = $jam < 12 ? 'Selamat pagi' : ($jam < 14 ? 'Selamat tengah hari' : ($jam < 19 ? 'Selamat petang' : 'Selamat malam'));
$hari  = ['Ahad','Isnin','Selasa','Rabu','Khamis','Jumaat','Sabtu'];
$bulan = ['','Januari','Februari','Mac','April','Mei','Jun','Julai','Ogos','September','Oktober','November','Disember'];
$hariIni = $hari[(int)date('w')] . ', ' . date('j') . ' ' . $bulan[(int)date('n')] . ' ' . date('Y');

// Donut chart (CSS conic-gradient)
$segmen = [[$menunggu, '#e39a1b'], [$lulus, '#3a9d5d'], [$tolak, '#d64545'], [$pulang, '#8a96a3']];
if ($jumlah === 0) {
    $donut = '#e3eaf3 0% 100%';
} else {
    $mula = 0; $bahagi = [];
    foreach ($segmen as $sg) {
        if ($sg[0] > 0) {
            $tamat = $mula + ($sg[0] / $jumlah * 100);
            $bahagi[] = $sg[1] . ' ' . number_format($mula, 2, '.', '') . '% ' . number_format($tamat, 2, '.', '') . '%';
            $mula = $tamat;
        }
    }
    $donut = implode(', ', $bahagi);
}

// Banner photo (same picture as homepage)
$bannerStyle = '';
foreach (['assets/img/pdkt.jpg', 'img/pdkt.jpg', 'assets/img/hero.jpg', 'img/hero.jpg'] as $rel) {
    if (file_exists(__DIR__ . '/../' . $rel)) {
        $bannerStyle = "background-image: linear-gradient(90deg, rgba(15,40,80,.92) 0%, rgba(15,40,80,.65) 60%, rgba(15,40,80,.35) 100%), url('" . BASE_URL . "/" . $rel . "');";
        break;
    }
}
$judul = 'Dashboard Staff';
$aktif = 'dashboard';
require __DIR__ . '/../includes/staff_top.php';
?>

    <div class="banner" style="<?= $bannerStyle ?>">
      <div>
        <div class="tarikh"><?= e($hariIni) ?></div>
        <h1><?= e($ucap) ?>, <?= e($profil['nama']) ?></h1>
        <p><?= e(trim($profil['jawatan'] . ($profil['bahagian'] ? ' · ' . $profil['bahagian'] : ''))) ?></p>
      </div>
    </div>

    <?php if ($lewat > 0): ?>
      <div class="amaran">
        <strong>Peringatan:</strong> Anda ada <?= $lewat ?> permohonan yang telah melepasi tarikh dijangka pulang. Sila pulangkan aset secepat mungkin.
      </div>
    <?php endif; ?>

    <div class="stat">
      <div class="kad">
        <div class="ikon i-kuning"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        <div><b><?= $menunggu ?></b><span>Menunggu kelulusan</span></div>
      </div>
      <div class="kad">
        <div class="ikon i-hijau"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/></svg></div>
        <div><b><?= $lulus ?></b><span>Diluluskan / dipinjam</span></div>
      </div>
      <div class="kad">
        <div class="ikon i-merah"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg></div>
        <div><b><?= $tolak ?></b><span>Ditolak</span></div>
      </div>
      <div class="kad">
        <div class="ikon i-kelabu"><svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></div>
        <div><b><?= $pulang ?></b><span>Telah dipulangkan</span></div>
      </div>
    </div>

    <div class="grid">
      <!-- Left: latest requests -->
      <div class="panel">
        <h2>Permohonan Terkini <small><a href="<?= BASE_URL ?>/staff/permohonan.php" style="color:var(--biru)">Lihat semua</a></small></h2>
        <?php if (!$senarai): ?>
          <div class="kosong">
            <svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="M9 11h6M9 15h4"/></svg>
            <b>Belum ada permohonan</b>
            <span>Anda belum membuat sebarang permohonan.</span>
            <span>Permohonan yang anda hantar akan dipaparkan di sini.</span>
          </div>
        <?php else: ?>
          <div class="jadual">
            <table>
              <thead>
                <tr><th>No. Permohonan</th><th>Aset</th><th>Tarikh Pinjam</th><th>Dijangka Pulang</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($senarai as $r): ?>
                  <tr>
                    <td><?= e($r['no_permohonan']) ?></td>
                    <td class="aset"><?= e($r['aset'] ?: '-') ?></td>
                    <td><?= e(tarikh($r['tarikh_pinjam'])) ?></td>
                    <td><?= e(tarikh($r['tarikh_dijangka_pulang'])) ?></td>
                    <td><span class="badge <?= e($r['status']) ?>"><?= e($label[$r['status']] ?? $r['status']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Right: donut + available assets -->
      <div>
        <div class="panel">
          <h2>Ringkasan Status</h2>
          <div class="donut-wrap">
            <div class="donut" style="background:conic-gradient(<?= $donut ?>);">
              <div class="tengah"><b><?= $jumlah ?></b><span>permohonan</span></div>
            </div>
            <div class="legenda">
              <div><i style="background:#e39a1b"></i>Menunggu (<?= $menunggu ?>)</div>
              <div><i style="background:#3a9d5d"></i>Diluluskan (<?= $lulus ?>)</div>
              <div><i style="background:#d64545"></i>Ditolak (<?= $tolak ?>)</div>
              <div><i style="background:#8a96a3"></i>Dipulangkan (<?= $pulang ?>)</div>
            </div>
          </div>
        </div>

        <div class="panel">
          <h2>Aset Tersedia <small><?= $tersedia ?> aset</small></h2>
          <?php if ($contoh): ?>
            <div class="senarai-aset">
              <?php foreach ($contoh as $a): ?>
                <div class="item">
                  <div class="kotak"><svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/></svg></div>
                  <div><b><?= e($a['keterangan']) ?></b><small><?= e($a['no_siri']) ?></small></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <a class="pautan-katalog" href="<?= BASE_URL ?>/staff/katalog.php">Semak Semua Aset</a>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/../includes/staff_bottom.php'; ?>