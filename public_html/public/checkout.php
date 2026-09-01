<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/stripe.php';
include_once __DIR__ . '/../includes/session_security.php';
include_once __DIR__ . '/../includes/security.php';
include_once __DIR__ . '/../includes/csrf.php';
include("../includes/header_public.php");

// session_start(); // Already handled by session_security.php included in header_public.php

// Verify cart is not empty
if (empty($_SESSION['cart'])) {
    header("Location: /cart");
    exit;
}

// Get Stripe public key
$stripePublicKey = getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_your_public_key_here';

// Calculate cart total and items
$cart_items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cart_key => $qty) {
        $parts = explode('::', $cart_key, 2);
        $product_id = (int)$parts[0];
        $custom_name = isset($parts[1]) ? base64_decode($parts[1]) : '';

        $stmt = $db->prepare("SELECT name, price FROM products WHERE id = ?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        if ($product) {
            $displayName = $product['name'] . ($custom_name !== '' ? ' - ' . $custom_name : '');
            $cart_items[] = [
                'name' => $displayName,
                'price' => $product['price'],
                'quantity' => $qty,
                'subtotal' => $product['price'] * $qty
            ];
            $total += $product['price'] * $qty;
        }
    }
}
?>

<div class="checkout-container">
    <h2>Secure Checkout</h2>

    <div class="checkout-content">
        <div class="checkout-form-section">
            <div class="checkout-card">
                <h3>Redirecting to Secure Checkout</h3>
                <p>Please wait while we redirect you to Stripe's secure payment page...</p>
                <div style="text-align: center; margin: 20px 0;">
                    <div class="spinner" style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
                <p style="font-size: 14px; color: #666;">If you're not redirected automatically, <a href="#" onclick="initialize()">click here</a>.</p>

                <div id="payment-message" class="hidden alert alert-danger" style="margin-top: 20px;"></div>
            </div>
        </div>

        <div class="checkout-summary-section">
            <div class="checkout-card">
                <h3>Order Summary</h3>
                <div class="order-summary">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <div class="item-info">
                            <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                            <span class="item-quantity">× <?= $item['quantity'] ?></span>
                        </div>
                        <span class="item-price">$<?= number_format($item['subtotal'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>

                    <div class="summary-divider"></div>

                    <div class="summary-total">
                        <strong>Total: $<?= number_format($total, 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const paymentMessage = document.querySelector("#payment-message");

    async function initialize() {
        try {

            const response = await fetch("/create", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ csrf_token: <?= json_encode($_SESSION['csrf_token']) ?> })
            });


            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }

            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            if (data.url) {
                window.location.href = data.url;
                return;
            }

            throw new Error("No checkout URL received from server");
        } catch (error) {
            showError("Failed to initialize checkout. Please refresh the page and try again.");
        }
    }

    function showError(errorMsgText) {
        paymentMessage.textContent = errorMsgText;
        paymentMessage.classList.remove("hidden");
    }

    initialize();
</script>

<style>
    .checkout-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    @media (max-width: 768px) {
        .checkout-content {
            grid-template-columns: 1fr;
        }
    }

    .hidden {
        display: none;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<?php include("../includes/footer.php"); ?>
