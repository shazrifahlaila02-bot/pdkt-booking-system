<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['staff']);
require_once __DIR__ . '/../dbconnect.php';

$uid = (int)$_SESSION['user_id'];
$flash = $_SESSION['flash_staff'] ?? '';
unset($_SESSION['flash_staff']);

$status = $_GET['status'] ?? '';
if (!in_array($status, ['menunggu', 'lulus', 'tidak_lulus', 'dipulangkan'], true)) { $status = ''; }

// ---- My requests ----
$sql = "SELECT p.no_permohonan, p.tujuan, p.tempat_digunakan, p.tarikh_pinjam, p.tarikh_dijangka_pulang, p.status,
               GROUP_CONCAT(a.keterangan SEPARATOR ', ') AS aset
        FROM permohonan p
        LEFT JOIN permohonan_aset pa ON pa.permohonan_id = p.id
        LEFT JOIN aset a ON a.id = pa.aset_id
        WHERE p.pemohon_id = ?" . ($status !== '' ? " AND p.status = ?" : "") . "
        GROUP BY p.id
        ORDER BY p.created_at DESC";
$stmt = mysqli_prepare($dbcon, $sql);
if ($status !== '') { mysqli_stmt_bind_param($stmt, "is", $uid, $status); }
else                { mysqli_stmt_bind_param($stmt, "i", $uid); }
mysqli_stmt_execute($stmt);
$senarai = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ---- Counts for the chips ----
$bil = ['semua' => 0, 'menunggu' => 0, 'lulus' => 0, 'tidak_lulus' => 0, 'dipulangkan' => 0];
$stmt = mysqli_prepare($dbcon, "SELECT status, COUNT(*) AS n FROM permohonan WHERE pemohon_id = ? GROUP BY status");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($rs)) { $bil[$r['status']] = (int)$r['n']; $bil['semua'] += (int)$r['n']; }
mysqli_stmt_close($stmt);

function pautanStatus($st) {
    return BASE_URL . '/staff/permohonan.php' . ($st !== '' ? '?status=' . urlencode($st) : '');
}
function tarikh($d) { return $d ? date('d/m/Y', strtotime($d)) : '-'; }
$label = ['menunggu' => 'Menunggu', 'lulus' => 'Diluluskan', 'tidak_lulus' => 'Ditolak', 'dipulangkan' => 'Dipulangkan'];

$extraCss = <<<'CSS'

    .btn-baru2 { background:var(--biru); color:#fff; padding:11px 20px; border-radius:10px; font-size:14px; font-weight:bold; }
    .btn-baru2:hover { background:var(--biru-gelap); }
    .panel.tanpa-anim { animation:none; }
    .berjaya { background:#e6f4ea; color:#1e6b34; border-left:4px solid #3a9d5d; padding:12px 14px; border-radius:8px; font-size:14px; margin-bottom:16px; }
CSS;

$judul = 'Permohonan Pinjaman';
$aktif = 'permohonan';
require __DIR__ . '/../includes/staff_top.php';
?>

    <div class="kepala">
      <div>
        <h1>Permohonan Pinjaman</h1>
        <p>Semua permohonan pinjaman aset yang telah anda hantar.</p>
      </div>
      <a class="btn-baru2" href="<?= BASE_URL ?>/staff/katalog.php">+ Mohon Pinjaman Baru</a>
    </div>

    <?php if ($flash): ?>
      <div class="berjaya"><?= e($flash) ?></div>
    <?php endif; ?>

    <div class="chips">
      <a class="chip <?= $status === '' ? 'aktif' : '' ?>" href="<?= e(pautanStatus('')) ?>">Semua<small><?= $bil['semua'] ?></small></a>
      <a class="chip <?= $status === 'menunggu' ? 'aktif' : '' ?>" href="<?= e(pautanStatus('menunggu')) ?>">Menunggu<small><?= $bil['menunggu'] ?></small></a>
      <a class="chip <?= $status === 'lulus' ? 'aktif' : '' ?>" href="<?= e(pautanStatus('lulus')) ?>">Diluluskan<small><?= $bil['lulus'] ?></small></a>
      <a class="chip <?= $status === 'tidak_lulus' ? 'aktif' : '' ?>" href="<?= e(pautanStatus('tidak_lulus')) ?>">Ditolak<small><?= $bil['tidak_lulus'] ?></small></a>
      <a class="chip <?= $status === 'dipulangkan' ? 'aktif' : '' ?>" href="<?= e(pautanStatus('dipulangkan')) ?>">Dipulangkan<small><?= $bil['dipulangkan'] ?></small></a>
    </div>

    <div class="panel">
      <?php if (!$senarai): ?>
        <div class="kosong">
          <svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="M9 11h6M9 15h4"/></svg>
          <b>Tiada permohonan</b>
          <span><?= $status === '' ? 'Anda belum membuat sebarang permohonan.' : 'Tiada permohonan dengan status ini.' ?></span>
        </div>
      <?php else: ?>
        <div class="jadual">
          <table>
            <thead>
              <tr><th>No. Permohonan</th><th>Aset</th><th>Tujuan</th><th>Tempat Digunakan</th><th>Tarikh Pinjam</th><th>Dijangka Pulang</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach ($senarai as $r): ?>
                <tr>
                  <td><?= e($r['no_permohonan']) ?></td>
                  <td class="aset"><?= e($r['aset'] ?: '-') ?></td>
                  <td class="aset"><?= e($r['tujuan'] ?: '-') ?></td>
                  <td class="aset"><?= e($r['tempat_digunakan'] ?: '-') ?></td>
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

<?php require __DIR__ . '/../includes/staff_bottom.php'; ?>