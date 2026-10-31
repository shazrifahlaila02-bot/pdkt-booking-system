<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['staff']);
require_once __DIR__ . '/../dbconnect.php';
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$uid = (int)$_SESSION['user_id'];
$id = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(400); exit('<!doctype html><html lang="ms"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Permohonan tidak tersedia</title></head><body><h1>Permohonan tidak tersedia</h1><p>Nombor permohonan tidak sah.</p><a href="' . BASE_URL . '/staff/permohonan.php">Kembali ke senarai permohonan</a></body></html>'); }
$stmt = mysqli_prepare($dbcon, 'SELECT id, no_permohonan, tujuan, status FROM permohonan WHERE id=? AND pemohon_id=?');
mysqli_stmt_bind_param($stmt, 'ii', $id, $uid);
mysqli_stmt_execute($stmt);
$rekod = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$rekod || $rekod['status'] !== 'menunggu') {
    http_response_code(403);
    exit('<!doctype html><html lang="ms"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Permohonan tidak tersedia</title></head><body><h1>Permohonan tidak tersedia</h1><p>Permohonan ini tidak boleh dipadam. Hanya permohonan sendiri yang masih menunggu kelulusan boleh dipadam.</p><a href="' . BASE_URL . '/staff/permohonan.php">Kembali ke senarai permohonan</a></body></html>');
}
$ralat = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
        http_response_code(403);
        $ralat = 'Sesi tidak sah. Sila muat semula halaman sebelum mencuba lagi.';
    } elseif (($_POST['sahkan'] ?? '') !== 'padam') {
        http_response_code(400);
        $ralat = 'Sila sahkan pemadaman permohonan.';
    } else {
        try {
            mysqli_begin_transaction($dbcon);
            $stmt = mysqli_prepare($dbcon, 'SELECT status FROM permohonan WHERE id=? AND pemohon_id=? FOR UPDATE');
            mysqli_stmt_bind_param($stmt, 'ii', $id, $uid);
            mysqli_stmt_execute($stmt);
            $semasa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$semasa || $semasa['status'] !== 'menunggu') throw new RuntimeException('Permohonan sudah diproses dan tidak boleh dipadam.');
            $stmt = mysqli_prepare($dbcon, 'DELETE FROM permohonan_aset WHERE permohonan_id=?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($dbcon, "DELETE FROM permohonan WHERE id=? AND pemohon_id=? AND status='menunggu'");
            mysqli_stmt_bind_param($stmt, 'ii', $id, $uid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            mysqli_commit($dbcon);
            $_SESSION['flash_staff'] = 'Permohonan ' . $rekod['no_permohonan'] . ' berjaya dipadam.';
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            header('Location: ' . BASE_URL . '/staff/permohonan.php');
            exit;
        } catch (Throwable $ex) {
            mysqli_rollback($dbcon);
            error_log('Pemadaman permohonan gagal: ' . $ex->getMessage());
            $ralat = get_class($ex) === RuntimeException::class ? $ex->getMessage() : 'Permohonan gagal dipadam. Sila cuba lagi.';
            http_response_code(409);
        }
    }
}
$judul = 'Padam Permohonan';
$aktif = 'permohonan';
$extraCss = '.pengesahan-padam{padding:26px;max-width:680px}.pengesahan-padam p{line-height:1.7}.pilihan-padam{display:flex;gap:12px;margin-top:24px;flex-wrap:wrap}.pilihan-padam a,.pilihan-padam button{padding:12px 20px;border-radius:8px;font:inherit;cursor:pointer}.pilihan-padam a{background:#eef2f7;color:#334155}.pilihan-padam button{border:0;background:#ad3030;color:#fff}.ralat-padam{color:#9b1c1c;background:#fde8e8;padding:14px;border-radius:8px}';
require __DIR__ . '/../includes/staff_top.php';
?>
<div class="kepala"><div><h1>Padam Permohonan</h1><p>Semak permohonan sebelum mengesahkan pemadaman.</p></div></div>
<section class="panel pengesahan-padam">
    <?php if ($ralat): ?><div class="ralat-padam" role="alert"><?= e($ralat) ?></div><?php endif; ?>
    <h2>Adakah anda pasti?</h2>
    <p>Permohonan <strong><?= e($rekod['no_permohonan']) ?></strong> akan dipadam. Tindakan ini tidak boleh dipulihkan.</p>
    <p><strong>Tujuan:</strong> <?= e($rekod['tujuan']) ?></p>
    <form method="post" action="<?= BASE_URL ?>/staff/padam.php">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="sahkan" value="padam">
        <div class="pilihan-padam"><a href="<?= BASE_URL ?>/staff/permohonan.php">Batal</a><button type="submit">Ya, Padam Permohonan</button></div>
    </form>
</section>
<?php require __DIR__ . '/../includes/staff_bottom.php'; ?>
