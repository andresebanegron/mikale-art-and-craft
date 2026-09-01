<?php
include_once __DIR__ . '/../includes/session_security.php';
include_once __DIR__ . '/../includes/security.php';
include_once __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../config/db.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $custom_name_raw = trim($_POST['custom_name'] ?? '');

    if ($product_id <= 0 || $quantity < 1 || $quantity > 1000 || mb_strlen($custom_name_raw, 'UTF-8') > 255) {
        header("Location: /");
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    $stmt->close();

    if (!$product) {
        die("Product not found.");
    }


    $personalizationLabel = trim($product['personalization']);
    if ($personalizationLabel === 'plaque_required' || $personalizationLabel === 'plaque_optional') {
        $personalizationLabel = 'Plaque name';
    }

    if ($personalizationLabel !== '' && $custom_name_raw === '') {
        die("This product requires personalization before it can be added to the cart.");
    }

    if ($personalizationLabel === '') {
        $custom_name_raw = '';
    }

    // Initialize cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Build session key. If custom name provided, append base64 to preserve characters.
    $key = (string)$product_id;
    if ($custom_name_raw !== '') {
        $key .= '::' . base64_encode($custom_name_raw);
    }

    // Add product to cart (merge quantities for identical key)
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key] += $quantity;
    } else {
        $_SESSION['cart'][$key] = $quantity;
    }

    header("Location: /");
    exit;
}

// Direct access without POST
header("Location: /");
exit;
?>