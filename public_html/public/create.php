<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/stripe.php';

try {
    $request = json_decode(file_get_contents('php://input'), true);
    if (!is_array($request)) {
        $request = [];
    }
    validate_csrf($request['csrf_token'] ?? null);

    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        throw new RuntimeException('Cart is empty.');
    }

    $lineItems = [];

    foreach ($_SESSION['cart'] as $cartKey => $qtyRaw) {
        $parts = explode('::', (string)$cartKey, 2);
        $productId = (int)$parts[0];
        $customName = isset($parts[1]) ? base64_decode($parts[1], true) : '';
        $qty = filter_var($qtyRaw, FILTER_VALIDATE_INT);

        if ($productId < 1 || $qty === false || $qty < 1 || $qty > 1000) {
            throw new RuntimeException('The cart contains an invalid quantity.');
        }
        if ($customName === false || mb_strlen($customName, 'UTF-8') > 255) {
            throw new RuntimeException('The cart contains invalid personalization data.');
        }

        $stmt = $db->prepare(
            "SELECT id, name, price, personalization
             FROM products
             WHERE id = ? AND active = 1"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            throw new RuntimeException('A product in your cart is no longer available.');
        }

        $requiresPersonalization = trim((string)$product['personalization']) !== '';
        if ($requiresPersonalization && $customName === '') {
            throw new RuntimeException('A product is missing required personalization.');
        }

        $displayName = (string)$product['name'];
        if ($customName !== '') {
            $displayName .= ' - ' . $customName;
        }

        $lineItems[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => $displayName],
                'unit_amount' => (int)round(((float)$product['price']) * 100),
            ],
            'quantity' => $qty,
        ];
    }

    if (!$lineItems) {
        throw new RuntimeException('No valid items were found in the cart.');
    }

    $checkoutDomain = rtrim((string)$domain, '/');
    if ($checkoutDomain === '' || $checkoutDomain === 'http://localhost') {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $checkoutDomain = $scheme . '://' . $host;
    }

    $checkoutSession = $stripe->checkout->sessions->create([
        'mode' => 'payment',
        'line_items' => $lineItems,
        'metadata' => [
            'cart' => json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE),
        ],
        'success_url' => $checkoutDomain . '/complete?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $checkoutDomain . '/cart',
    ]);

    echo json_encode(['url' => $checkoutSession->url]);
} catch (Throwable $e) {
    error_log('Checkout creation failed: ' . $e->getMessage());
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['error' => 'Unable to start checkout. Please review your cart and try again.']);
}
