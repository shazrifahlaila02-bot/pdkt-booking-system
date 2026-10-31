<?php
/**
 * includes/auth.php
 * Session start + login/role guards. Include at the top of every protected page.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Name of your project folder inside htdocs (change if different)
define('BASE_URL', '/PDKT');

// Where each role lands after login
function dashboard_url($peranan) {
    if ($peranan === 'admin') {
        return BASE_URL . '/admin/dashboard.php';
    }
    return BASE_URL . '/staff/dashboard.php';
}

// Must be logged in
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// Must be logged in AND have one of the allowed roles
function require_role(array $roles) {
    require_login();
    if (!in_array($_SESSION['peranan'], $roles, true)) {
        header('Location: ' . dashboard_url($_SESSION['peranan']));
        exit;
    }
}

// Escape output to prevent XSS
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
