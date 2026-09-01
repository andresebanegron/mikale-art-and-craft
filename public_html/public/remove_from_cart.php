<?php
include_once __DIR__ . '/../includes/session_security.php';
include_once __DIR__ . '/../includes/security.php';
include_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $key = $_POST['product_key'] ?? '';
    $qty = intval($_POST['quantity']);
    if ($qty < 1) {
        header("Location: /cart");
        exit;
    }
    if ($key !== '' && isset($_SESSION['cart'][$key])) {
        if ($qty >= $_SESSION['cart'][$key]) {
            unset($_SESSION['cart'][$key]);
        } else {
            $_SESSION['cart'][$key] -= $qty;
        }
    }
} else {
    // fallback for old GET link
    $id = $_GET['id'] ?? '';
    if ($id !== '' && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
}

header("Location: /cart");
exit;