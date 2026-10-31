<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['staff']);
require_once __DIR__ . '/../dbconnect.php';

$uid = (int)$_SESSION['user_id'];
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

// ---- Applicant details (shown read-only, like the top box of KEW.PA-9) ----
$stmt = mysqli_prepare($dbcon, "SELECT nama, jawatan, bahagian FROM pengguna WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$profil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: ['nama' => $_SESSION['nama'], 'jawatan' => '', 'bahagian' => ''];
mysqli_stmt_close($stmt);

// ---- Assets that can be requested ----
$tersediaList = mysqli_fetch_all(mysqli_query($dbcon,
    "SELECT id, keterangan, no_siri FROM aset WHERE status = 'tersedia' ORDER BY keterangan"), MYSQLI_ASSOC);
$validIds = array_map(function ($a) { return (int)$a['id']; }, $tersediaList);

function tarikhSah($s) {
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}

$hariIni = date('Y-m-d');
$errors  = [];
$old     = ['tujuan' => '', 'tempat' => '', 'pinjam' => $hariIni, 'pulang' => ''];
$dipilih = [];

// Pre-select the asset chosen from the catalog (?aset=ID)
if (isset($_GET['aset']) && in_array((int)$_GET['aset'], $validIds, true)) {
    $dipilih = [(int)$_GET['aset']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['tujuan'] = trim($_POST['tujuan'] ?? '');
    $old['tempat'] = trim($_POST['tempat'] ?? '');
    $old['pinjam'] = trim($_POST['pinjam'] ?? '');
    $old['pulang'] = trim($_POST['pulang'] ?? '');
    $dipilih = array_values(array_unique(array_map('intval', (array)($_POST['aset'] ?? []))));

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Sesi tidak sah. Sila cuba lagi.';
    }
    if ($old['tujuan'] === '' || mb_strlen($old['tujuan']) > 255) { $errors[] = 'Sila isi tujuan (maksimum 255 aksara).'; }
    if ($old['tempat'] === '' || mb_strlen($old['tempat']) > 255) { $errors[] = 'Sila isi tempat digunakan (maksimum 255 aksara).'; }

    if (!tarikhSah($old['pinjam'])) {
        $errors[] = 'Tarikh pinjam tidak sah.';
    } elseif ($old['pinjam'] < $hariIni) {
        $errors[] = 'Tarikh pinjam tidak boleh sebelum hari ini.';
    }
    if (!tarikhSah($old['pulang'])) {
        $errors[] = 'Tarikh dijangka pulang tidak sah.';
    } elseif (tarikhSah($old['pinjam']) && $old['pulang'] < $old['pinjam']) {
        $errors[] = 'Tarikh dijangka pulang tidak boleh sebelum tarikh pinjam.';
    }

    if (!$dipilih) {
        $errors[] = 'Sila pilih sekurang-kurangnya satu aset.';
    } else {
        foreach ($dipilih as $id) {
            if (!in_array($id, $validIds, true)) { $errors[] = 'Ada aset yang dipilih tidak lagi tersedia.'; break; }
        }
    }

    // Do not allow overlapping with an already APPROVED loan of the same asset
    if (!$errors) {
        $chk = mysqli_prepare($dbcon,
            "SELECT COUNT(*) FROM permohonan p JOIN permohonan_aset pa ON pa.permohonan_id = p.id
             WHERE pa.aset_id = ? AND p.status = 'lulus' AND p.tarikh_pinjam <= ? AND p.tarikh_dijangka_pulang >= ?");
        foreach ($dipilih as $id) {
            mysqli_stmt_bind_param($chk, "iss", $id, $old['pulang'], $old['pinjam']);
            mysqli_stmt_execute($chk);
            mysqli_stmt_bind_result($chk, $n);
            mysqli_stmt_fetch($chk);
            if ($n > 0) {
                foreach ($tersediaList as $a) {
                    if ((int)$a['id'] === $id) { $errors[] = '"' . $a['keterangan'] . '" sudah ditempah pada tarikh tersebut.'; }
                }
            }
        }
        mysqli_stmt_close($chk);
    }

    if (!$errors) {
        try {
            mysqli_begin_transaction($dbcon);

            $tmp = 'TMP' . bin2hex(random_bytes(6));
            $ins = mysqli_prepare($dbcon,
                "INSERT INTO permohonan (no_permohonan, pemohon_id, tujuan, tempat_digunakan, tarikh_pinjam, tarikh_dijangka_pulang, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'menunggu')");
            mysqli_stmt_bind_param($ins, "sissss", $tmp, $uid, $old['tujuan'], $old['tempat'], $old['pinjam'], $old['pulang']);
            mysqli_stmt_execute($ins);
            $pid = mysqli_insert_id($dbcon);
            mysqli_stmt_close($ins);

            // Real application number: PDKT/2026/0001
            $noPermohonan = sprintf('PDKT/%s/%04d', date('Y'), $pid);
            $upd = mysqli_prepare($dbcon, "UPDATE permohonan SET no_permohonan = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "si", $noPermohonan, $pid);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $pa = mysqli_prepare($dbcon, "INSERT INTO permohonan_aset (permohonan_id, aset_id) VALUES (?, ?)");
            foreach ($dipilih as $id) {
                mysqli_stmt_bind_param($pa, "ii", $pid, $id);
                mysqli_stmt_execute($pa);
            }
            mysqli_stmt_close($pa);

            mysqli_commit($dbcon);

            $_SESSION['flash_staff'] = 'Permohonan ' . $noPermohonan . ' berjaya dihantar dan sedang menunggu kelulusan Pentadbir.';
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            header('Location: ' . BASE_URL . '/staff/permohonan.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            mysqli_rollback($dbcon);
            error_log('Permohonan gagal: ' . $ex->getMessage());
            $errors[] = 'Permohonan gagal dihantar. Sila cuba lagi.';
        }
    }
}

$extraCss = <<<'CSS'

    .borang { display:grid; grid-template-columns:3fr 2fr; gap:18px; align-items:start; }
    .panel .dalam { padding:6px 22px 22px; }
    .borang label { display:block; font-size:13px; font-weight:bold; margin:16px 0 6px; color:#35424f; }
    .borang input[type=text], .borang input[type=date] { width:100%; padding:11px 12px; border:1px solid #cbd3dc; border-radius:8px; font-size:14px; background:#fafbfc; font-family:inherit; }
    .borang input:focus, .borang select:focus { outline:none; border-color:var(--biru); background:#fff; box-shadow:0 0 0 3px rgba(31,78,140,.18); }
    .baris2 { display:grid; grid-template-columns:1fr 1fr; gap:0 14px; }
    .pemohon-box { background:#f4f7fb; border-radius:10px; padding:14px 16px; margin-top:16px; display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px 18px; }
    .pemohon-box small { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:var(--kelabu); }
    .pemohon-box span { font-size:14px; font-weight:bold; }
    .nota { font-size:12px; color:var(--kelabu); margin-top:4px; }

    .tambah { display:flex; gap:8px; margin-top:16px; }
    .tambah select { flex:1; padding:11px 12px; border:1px solid #cbd3dc; border-radius:8px; font-size:14px; background:#fff; min-width:0; }
    .tambah button { padding:0 16px; border-radius:8px; border:1px solid var(--biru); background:#fff; color:var(--biru); cursor:pointer; font-size:14px; }
    .tambah button:hover { background:#eaf0f8; }
    .dipilih { margin-top:14px; display:flex; flex-direction:column; gap:8px; }
    .item-aset { display:flex; align-items:center; gap:12px; padding:10px 12px; border:1px solid #dbe5f3; background:#f7faff; border-radius:10px; }
    .item-aset .kotak { width:34px; height:34px; border-radius:9px; background:#e6f4ea; display:flex; align-items:center; justify-content:center; flex:0 0 34px; }
    .item-aset .kotak svg { width:18px; height:18px; stroke:#1e6b34; fill:none; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
    .item-aset .info { flex:1; min-width:0; }
    .item-aset .info b { display:block; font-size:14px; } .item-aset .info small { color:var(--kelabu); font-size:12px; }
    .item-aset .buang { border:0; background:#fde8e8; color:#9b1c1c; width:28px; height:28px; border-radius:50%; cursor:pointer; font-size:16px; line-height:1; }
    .item-aset .buang:hover { background:#f9cfcf; }
    .kosong-aset { text-align:center; color:var(--kelabu); font-size:13px; padding:18px; border:1px dashed #c9d4e3; border-radius:10px; }

    .ralat { background:#fde8e8; color:#9b1c1c; border-left:4px solid #d64545; padding:12px 14px 12px 30px; border-radius:8px; font-size:14px; margin-bottom:16px; }
    .ralat ul { margin:0; padding:0; }
    .hantar { display:flex; gap:12px; justify-content:flex-end; margin-top:20px; flex-wrap:wrap; }
    .hantar a, .hantar button { padding:12px 24px; border-radius:10px; font-size:15px; cursor:pointer; }
    .hantar a { border:1px solid #cbd3dc; color:#35424f; background:#fff; }
    .hantar button { border:0; background:var(--biru); color:#fff; font-weight:bold; }
    .hantar button:hover { background:var(--biru-gelap); }
    @media (max-width:900px) { .borang { grid-template-columns:1fr; } .baris2 { grid-template-columns:1fr; } }
CSS;

$judul = 'Mohon Pinjaman';
$aktif = 'permohonan';
require __DIR__ . '/../includes/staff_top.php';
?>

    <div class="kepala">
      <div>
        <h1>Borang Permohonan Pinjaman Aset</h1>
        <p>Berdasarkan borang KEW.PA-9. Permohonan akan disemak dan diluluskan oleh Pentadbir.</p>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="ralat"><ul>
        <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <?php if (!$tersediaList): ?>
      <div class="panel"><div class="kosong">
        <svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/></svg>
        <b>Tiada aset tersedia</b>
        <span>Semua aset sedang dipinjam atau tidak boleh digunakan buat masa ini.</span>
      </div></div>
    <?php else: ?>
    <form method="post" action="" id="borang">
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

      <div class="borang">
        <!-- Left: request details -->
        <div class="panel">
          <h2>Maklumat Permohonan</h2>
          <div class="dalam">
            <div class="pemohon-box">
              <div><small>Nama Pemohon</small><span><?= e($profil['nama']) ?></span></div>
              <div><small>Jawatan</small><span><?= e($profil['jawatan'] ?: '-') ?></span></div>
              <div><small>Bahagian</small><span><?= e($profil['bahagian'] ?: '-') ?></span></div>
            </div>

            <label for="tujuan">Tujuan</label>
            <input type="text" id="tujuan" name="tujuan" maxlength="255" value="<?= e($old['tujuan']) ?>" placeholder="Cth: Mesyuarat Jawatankuasa Daerah" required>

            <label for="tempat">Tempat Digunakan</label>
            <input type="text" id="tempat" name="tempat" maxlength="255" value="<?= e($old['tempat']) ?>" placeholder="Cth: Bilik Mesyuarat Utama" required>

            <div class="baris2">
              <div>
                <label for="pinjam">Tarikh Pinjam</label>
                <input type="date" id="pinjam" name="pinjam" min="<?= e($hariIni) ?>" value="<?= e($old['pinjam']) ?>" required>
              </div>
              <div>
                <label for="pulang">Tarikh Dijangka Pulang</label>
                <input type="date" id="pulang" name="pulang" min="<?= e($hariIni) ?>" value="<?= e($old['pulang']) ?>" required>
              </div>
            </div>
            <div class="nota">Nama pengeluar dan kelulusan akan diisi oleh Pentadbir.</div>
          </div>
        </div>

        <!-- Right: asset picker -->
        <div class="panel">
          <h2>Aset Dipinjam <small id="bil">0 dipilih</small></h2>
          <div class="dalam">
            <div class="tambah">
              <select id="pilih-aset" aria-label="Pilih aset"></select>
              <button type="button" id="btn-tambah">Tambah</button>
            </div>
            <div class="dipilih" id="senarai"></div>
          </div>
        </div>
      </div>

      <div class="hantar">
        <a href="<?= BASE_URL ?>/staff/katalog.php">Batal</a>
        <button type="submit">Hantar Permohonan</button>
      </div>
    </form>

    <script>
      var SEMUA   = <?= json_encode($tersediaList, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      var dipilih = <?= json_encode($dipilih) ?>;

      var sel = document.getElementById('pilih-aset'),
          senarai = document.getElementById('senarai'),
          bil = document.getElementById('bil');

      function cari(id) { for (var i = 0; i < SEMUA.length; i++) { if (+SEMUA[i].id === +id) return SEMUA[i]; } }

      function lukis() {
        // dropdown: only assets not yet chosen
        sel.innerHTML = '';
        var ada = false;
        SEMUA.forEach(function (a) {
          if (dipilih.indexOf(+a.id) === -1) {
            var o = document.createElement('option');
            o.value = a.id; o.textContent = a.keterangan + ' (' + a.no_siri + ')';
            sel.appendChild(o); ada = true;
          }
        });
        if (!ada) { var o = document.createElement('option'); o.textContent = 'Semua aset telah dipilih'; sel.appendChild(o); }
        sel.disabled = !ada;
        document.getElementById('btn-tambah').disabled = !ada;

        // chosen list
        senarai.innerHTML = '';
        if (!dipilih.length) {
          senarai.innerHTML = '<div class="kosong-aset">Belum ada aset dipilih. Pilih daripada senarai di atas.</div>';
        }
        dipilih.forEach(function (id) {
          var a = cari(id); if (!a) return;
          var d = document.createElement('div'); d.className = 'item-aset';
          d.innerHTML =
            '<div class="kotak"><svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/></svg></div>' +
            '<div class="info"><b></b><small></small></div>' +
            '<input type="hidden" name="aset[]" value="' + (+a.id) + '">' +
            '<button type="button" class="buang" title="Buang" aria-label="Buang aset">&times;</button>';
          d.querySelector('b').textContent = a.keterangan;
          d.querySelector('small').textContent = a.no_siri;
          d.querySelector('.buang').addEventListener('click', function () {
            dipilih = dipilih.filter(function (x) { return +x !== +a.id; });
            lukis();
          });
          senarai.appendChild(d);
        });
        bil.textContent = dipilih.length + ' dipilih';
      }

      document.getElementById('btn-tambah').addEventListener('click', function () {
        if (sel.value) { dipilih.push(+sel.value); lukis(); }
      });

      // keep return date >= loan date
      var p1 = document.getElementById('pinjam'), p2 = document.getElementById('pulang');
      p1.addEventListener('change', function () {
        p2.min = p1.value;
        if (p2.value && p2.value < p1.value) p2.value = p1.value;
      });
      p2.min = p1.value || p2.min;

      lukis();
    </script>
    <?php endif; ?>

<?php require __DIR__ . '/../includes/staff_bottom.php'; ?>