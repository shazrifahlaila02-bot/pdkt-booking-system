<?php
require_once __DIR__ . '/includes/auth.php';
header('Content-Type: application/json; charset=UTF-8');
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['aktif' => false]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        echo json_encode(['aktif' => false, 'ralat' => 'Sesi tidak sah.']);
        exit;
    }
    $_SESSION['last_activity'] = time();
}
$expires = (int)$_SESSION['last_activity'] + PDKT_IDLE_SECONDS;
header('X-PDKT-Expires: ' . $expires);
echo json_encode(['aktif' => true, 'tamat' => $expires]);
