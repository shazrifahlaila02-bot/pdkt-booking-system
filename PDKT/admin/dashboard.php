<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin']);
?>
<!doctype html>
<html lang="ms"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Papan Pemuka Pentadbir — PDKT</title><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tema.css"><script src="<?= BASE_URL ?>/assets/js/antara-muka.js" defer></script></head>
<body class="halaman-dalaman"><a class="langkau" href="#kandungan">Langkau ke kandungan</a><div class="app">
<aside class="sisi" id="navigasi-sisi" aria-label="Navigasi pentadbir"><a class="jenama" href="<?= BASE_URL ?>/admin/dashboard.php"><span class="lencana-jenama" aria-hidden="true">P.</span><span><b>Pinjaman Aset</b><small>PEJABAT DAERAH KUALA TERENGGANU</small></span></a><div class="label-menu">RUANG PENTADBIRAN</div><nav class="menu"><a class="aktif" href="<?= BASE_URL ?>/admin/dashboard.php">Papan Pemuka</a><a href="<?= BASE_URL ?>/index.php#katalog">Katalog Aset</a></nav><div class="pengguna"><div class="nama"><?= e($_SESSION['nama']) ?></div><div class="peranan">Pentadbir sistem</div><a class="keluar" href="<?= BASE_URL ?>/logout.php">Log Keluar</a></div></aside>
<main class="isi" id="kandungan"><div class="bar-atas"><div><button class="buka-menu" type="button" aria-expanded="false" aria-controls="navigasi-sisi" aria-label="Buka menu navigasi">☰</button><span>Portal Pentadbir <span class="pemisah">/</span> <strong>Papan Pemuka</strong></span></div><a class="pautan-utama" href="<?= BASE_URL ?>/index.php">Laman utama →</a></div>
<div class="banner"><div><span class="label-sistem">PENTADBIRAN ASET PDKT</span><h1>Selamat datang, <?= e($_SESSION['nama']) ?></h1><p>Ruang pentadbiran Sistem Pinjaman Aset Alih.</p></div></div>
<section class="panel admin-pengenalan"><span class="tajuk-kecil">PENGURUSAN YANG LEBIH TERATUR</span><h2>Satu ruang untuk urusan aset.</h2><p>Semak senarai aset pejabat melalui katalog. Permohonan kakitangan perlu mendapat kelulusan sebelum aset boleh dipinjam.</p><a class="pautan-katalog" href="<?= BASE_URL ?>/index.php#katalog">Lihat Katalog Aset →</a></section>
<div class="nota-pentadbir"><strong>Makluman pembangunan</strong><p>Modul kelulusan dan pengurusan aset masih dalam pembangunan dan belum tersedia untuk digunakan.</p></div>
<footer class="kaki-dalaman">&copy; <?= date('Y') ?> Pejabat Daerah Kuala Terengganu <span>Sistem Pinjaman Aset Alih · KEW.PA-9</span></footer>
</main></div></body></html>
