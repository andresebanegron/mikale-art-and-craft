<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

validate_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid product.');
}

$stmt = $db->prepare('UPDATE products SET active = 0 WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

header('Location: admin_products.php');
exit;
