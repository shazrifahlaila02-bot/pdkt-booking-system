<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin']);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Admin</title>
  <style>
    body { font-family: Arial, sans-serif; margin:0; background:#f0f3f7; }
    header { background:#1f4e8c; color:#fff; padding:14px 24px; display:flex; justify-content:space-between; align-items:center; }
    header a { color:#fff; text-decoration:none; border:1px solid #fff; padding:6px 12px; border-radius:6px; }
    main { padding:24px; }
  </style>
</head>
<body>
  <header>
    <strong>Dashboard Admin</strong>
    <span><?= e($_SESSION['nama']) ?> &nbsp; <a href="<?= BASE_URL ?>/logout.php">Log Keluar</a></span>
  </header>
  <main>
    <h2>Selamat datang, <?= e($_SESSION['nama']) ?></h2>
    <p>Halaman ini dalam pembinaan.</p>
  </main>
</body>
</html>
