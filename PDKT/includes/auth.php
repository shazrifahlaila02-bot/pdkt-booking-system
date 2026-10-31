<?php
/** Pengesahan berasingan bagi setiap tab. Semua halaman PHP mesti memuat fail ini dahulu. */
if (!defined('BASE_URL')) define('BASE_URL', '/PDKT');
if (!defined('PDKT_IDLE_SECONDS')) define('PDKT_IDLE_SECONDS', 15 * 60);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$tabToken = $_SERVER['HTTP_X_PDKT_TAB'] ?? '';
if ($tabToken === '') {
    // Tiada maklumat pengguna dihantar sebelum tab membuktikan sesinya.
    header('Content-Type: text/html; charset=UTF-8');
    ?><!doctype html><html lang="ms"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Menyemak sesi — PDKT</title><script src="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/js/sesi-tab.js" defer></script></head><body><p id="status-sesi" role="status">Sedang menyemak sesi tab ini…</p><noscript>JavaScript diperlukan untuk sesi berasingan bagi setiap tab. Sila aktifkan JavaScript dan muat semula halaman ini.</noscript></body></html><?php
    exit;
}
if (!preg_match('/\A[a-f0-9]{64}\z/D', $tabToken)) {
    http_response_code(400);
    exit('Pengenalan tab tidak sah. Sila muat semula halaman.');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    // Kuki berasingan dipilih dengan token tab; kuki PHP tidak menentukan sesi tab lain.
    session_name('PDKT' . substr(hash('sha256', $tabToken), 0, 32));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_URL . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

$isSessionEndpoint = basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'session.php';
if (!empty($_SESSION['user_id'])) {
    if (empty($_SESSION['last_activity']) || time() - (int)$_SESSION['last_activity'] >= PDKT_IDLE_SECONDS) {
        $_SESSION = ['sesi_tamat' => true];
        session_regenerate_id(true);
    } elseif (!$isSessionEndpoint) {
        $_SESSION['last_activity'] = time();
    }
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
header('X-PDKT-CSRF: ' . $_SESSION['csrf']);
if (!empty($_SESSION['user_id'])) header('X-PDKT-Expires: ' . ((int)$_SESSION['last_activity'] + PDKT_IDLE_SECONDS));

// Skrip juga dimuat semula selepas navigasi dalaman tanpa mendedahkan token dalam URL.
ob_start(function ($html) {
    if (stripos($html, '</head>') !== false) {
        $script = '<script src="' . BASE_URL . '/assets/js/sesi-tab.js" defer></script>';
        $html = preg_replace('~</head>~i', $script . '</head>', $html, 1);
    }
    return $html;
});

function dashboard_url($peranan) {
    return BASE_URL . ($peranan === 'admin' ? '/admin/dashboard.php' : '/staff/dashboard.php');
}
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}
function require_role(array $roles) {
    require_login();
    if (!in_array($_SESSION['peranan'], $roles, true)) {
        header('Location: ' . dashboard_url($_SESSION['peranan']));
        exit;
    }
}
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
