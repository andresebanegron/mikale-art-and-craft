<?php
require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/security.php';

$adminId = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
if (!$adminId || $adminId < 1) {
    header('Location: /admin-portal');
    exit;
}

$stmt = $db->prepare('SELECT id FROM admins WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin-portal');
    exit;
}
