<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/stripe.php';
require __DIR__ . '/../includes/session_security.php';
require __DIR__ . '/../includes/mail.php';
require __DIR__ . '/../includes/csrf.php';
include("../includes/header_public.php");

$session_id = $_GET['session_id'] ?? null;
$status = 'unknown';
$payment_status = 'unknown';
$order_info = null;
$address_error = null;
$address_saved = $session_id && (($_SESSION['shipping_saved_for'] ?? null) === $session_id);

if ($session_id) {
    try {
        $session = $stripe->checkout->sessions->retrieve($session_id, [
            'expand' => ['payment_intent', 'line_items']
        ]);

        $status = $session->status;
        $payment_status = $session->payment_status;

        if ($payment_status === 'paid') {
            if (!isset($_SESSION['stripe_order_saved']) || $_SESSION['stripe_order_saved'] !== $session_id) {
                $order_info = saveStripeOrder($db, $session);
                if ($order_info) {
                    $_SESSION['stripe_order_saved'] = $session_id;
                    $_SESSION['stripe_order_id'] = (int)$order_info['id'];
                }
            } else {
                $order_id = (int)($_SESSION['stripe_order_id'] ?? 0);
                $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
                $stmt->bind_param('i', $order_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $order_info = $result->fetch_assoc();
            }

            if ($order_info && !$address_saved && $_SERVER['REQUEST_METHOD'] === 'POST') {
                validate_csrf();

                $full_name = trim($_POST['full_name'] ?? '');
                $address_line_1 = trim($_POST['address_line_1'] ?? '');
                $address_line_2 = trim($_POST['address_line_2'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $state = trim($_POST['state'] ?? '');
                $postal_code = trim($_POST['postal_code'] ?? '');
                $country = trim($_POST['country'] ?? '');

                if ($full_name === '' || $address_line_1 === '' || $city === '' ||
                    $state === '' || $postal_code === '' || $country === '') {
                    $address_error = 'Please complete all required shipping fields.';
                } elseif (strlen($full_name) > 255 || strlen($address_line_1) > 255 ||
                          strlen($address_line_2) > 255 || strlen($city) > 100 ||
                          strlen($state) > 100 || strlen($postal_code) > 30 || strlen($country) > 100) {
                    $address_error = 'One or more shipping fields are too long.';
                } else {
                    $address_parts = [$address_line_1];
                    if ($address_line_2 !== '') {
                        $address_parts[] = $address_line_2;
                    }
                    $address_parts[] = $city . ', ' . $state . ' ' . $postal_code;
                    $address_parts[] = $country;
                    $shipping_address = implode("\n", $address_parts);

                    $stmt = $db->prepare("UPDATE orders SET customer_name = ?, address = ? WHERE id = ?");
                    $order_id = (int)$order_info['id'];
                    $stmt->bind_param('ssi', $full_name, $shipping_address, $order_id);
                    $stmt->execute();

                    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
                    $stmt->bind_param('i', $order_id);
                    $stmt->execute();
                    $order_info = $stmt->get_result()->fetch_assoc();
                    $address_saved = true;
                    $_SESSION['shipping_saved_for'] = $session_id;
                    unset($_SESSION['cart']);
                    sendOrderCreationNotificationToAdmin($order_info);
                }
            }
        }
    } catch (Throwable $e) {
        error_log('Order completion failed: ' . $e->getMessage());
        $error_message = 'Unable to verify or complete this order right now.';
    }
}

function saveStripeOrder($db, $session) {
    $stripeSessionId = (string)($session->id ?? '');
    if ($stripeSessionId === '') {
        throw new RuntimeException('Missing Stripe session identifier.');
    }

    // Idempotency: if this Stripe Checkout Session has already produced an
    // order, return that order instead of creating another one.
    $stmt = $db->prepare("SELECT * FROM orders WHERE stripe_session_id = ? LIMIT 1");
    $stmt->bind_param('s', $stripeSessionId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if ($existing) {
        return $existing;
    }

    $customerEmail = trim((string)($session->customer_details->email ?? $session->customer_email ?? ''));
    $customerName = trim((string)($session->customer_details->name ?? ''));
    $customerAddress = '';

    $cartItems = [];
    if (!empty($session->metadata->cart)) {
        $cartItems = json_decode((string)$session->metadata->cart, true);
    }
    if (empty($cartItems) || !is_array($cartItems)) {
        throw new RuntimeException('Stripe session does not contain a valid cart.');
    }

    $paymentMethod = 'stripe';
    // Store the amount Stripe confirms was actually paid. Re-reading current
    // product prices could make the order total differ from the charge.
    $total = ((int)($session->amount_total ?? 0)) / 100;
    $trackingCode = 'ORD-' . strtoupper(bin2hex(random_bytes(8)));

    $db->begin_transaction();
    try {
        $stmt = $db->prepare("INSERT INTO orders
            (email, customer_name, address, payment_method, status, tracking_code, total, stripe_session_id)
            VALUES (?, ?, ?, ?, 'Processing', ?, ?, ?)");
        $stmt->bind_param(
            'sssssds',
            $customerEmail,
            $customerName,
            $customerAddress,
            $paymentMethod,
            $trackingCode,
            $total,
            $stripeSessionId
        );
        $stmt->execute();
        $orderId = $db->insert_id;

        foreach ($cartItems as $cartKey => $qtyRaw) {
            $parts = explode('::', (string)$cartKey, 2);
            $productId = (int)$parts[0];
            $quantity = filter_var($qtyRaw, FILTER_VALIDATE_INT);
            $customText = isset($parts[1]) ? base64_decode($parts[1], true) : null;

            if ($productId < 1 || $quantity === false || $quantity < 1 || $quantity > 1000) {
                throw new RuntimeException('Invalid order item quantity.');
            }
            if ($customText === false || ($customText !== null && mb_strlen($customText, 'UTF-8') > 255)) {
                throw new RuntimeException('Invalid order personalization.');
            }

            $stmt = $db->prepare("INSERT INTO order_items
                (order_id, product_id, quantity, custom_text) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiis', $orderId, $productId, $quantity, $customText);
            $stmt->execute();
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();

        // A concurrent retry may have inserted the same unique Stripe session.
        $stmt = $db->prepare("SELECT * FROM orders WHERE stripe_session_id = ? LIMIT 1");
        $stmt->bind_param('s', $stripeSessionId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
            return $existing;
        }
        throw $e;
    }

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>

<main class="checkout-container shipping-flow">
            <?php if ($payment_status === 'paid' && !$address_saved): ?>
                <div class="alert alert-success">
                    <h2>✓ Payment Successful!</h2>
                    <p>Your payment is confirmed. Please tell us where to ship your order.</p>
                </div>

                <?php if ($order_info): ?>
                    <div class="checkout-card shipping-card">
                        <h3>Shipping Address</h3>
                        <?php if ($address_error): ?>
                            <div class="error-message"><?php echo htmlspecialchars($address_error); ?></div>
                        <?php endif; ?>
                        <form method="post" class="checkout-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="form-group">
                                <label for="full_name">Full name</label>
                                <input id="full_name" name="full_name" type="text" autocomplete="name" maxlength="255" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? $order_info['customer_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="address_line_1">Street address</label>
                                <input id="address_line_1" name="address_line_1" type="text" autocomplete="address-line1" maxlength="255" required value="<?php echo htmlspecialchars($_POST['address_line_1'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="address_line_2">Apartment, suite, etc. <span>(optional)</span></label>
                                <input id="address_line_2" name="address_line_2" type="text" autocomplete="address-line2" maxlength="255" value="<?php echo htmlspecialchars($_POST['address_line_2'] ?? ''); ?>">
                            </div>
                            <div class="shipping-form-grid">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input id="city" name="city" type="text" autocomplete="address-level2" maxlength="100" required value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="state">State / Province</label>
                                    <input id="state" name="state" type="text" autocomplete="address-level1" maxlength="100" required value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="postal_code">Postal code</label>
                                    <input id="postal_code" name="postal_code" type="text" autocomplete="postal-code" maxlength="30" required value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input id="country" name="country" type="text" autocomplete="country-name" maxlength="100" required value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-large">Save Address &amp; Complete Order</button>
                            <p class="shipping-note">Your order will not be prepared for shipping until this address is saved.</p>
                        </form>
                    </div>
                <?php endif; ?>

            <?php elseif ($payment_status === 'paid' && $address_saved): ?>
                <div class="alert alert-success">
                    <h2>✓ Order Complete!</h2>
                    <p>Thank you. Your payment and shipping address have been received.</p>
                </div>

                <?php if ($order_info): ?>
                    <div class="checkout-card">
                        <h3>Order Details</h3>
                        <div class="order-info-grid">
                            <div class="info-item"><strong>Tracking Code</strong><?php echo htmlspecialchars($order_info['tracking_code']); ?></div>
                            <div class="info-item"><strong>Order Date</strong><?php echo date('M d, Y', strtotime($order_info['created_at'])); ?></div>
                            <div class="info-item"><strong>Status</strong><?php echo htmlspecialchars($order_info['status']); ?></div>
                            <div class="info-item"><strong>Total</strong>$<?php echo number_format($order_info['total'], 2); ?></div>
                            <div class="info-item"><strong>Ship To</strong><?php echo nl2br(htmlspecialchars($order_info['address'])); ?></div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="/track" class="btn btn-primary">
                            Track Your Order
                        </a>
                        <a href="/home" class="btn btn-secondary">
                            Continue Shopping
                        </a>
                    </div>
                <?php endif; ?>

            <?php elseif ($payment_status === 'unpaid'): ?>
                <div class="alert alert-warning">
                    <h2>⚠ Payment Pending</h2>
                    <p>Your payment is still processing. Please check back shortly.</p>
                </div>
                <a href="/checkout" class="btn btn-primary">
                    Return to Checkout
                </a>

            <?php else: ?>
                <div class="alert alert-danger">
                    <h2>✗ Payment Failed</h2>
                    <p>Your payment could not be processed. Please try again.</p>
                </div>
                <a href="/checkout" class="btn btn-primary">
                    Try Again
                </a>
            <?php endif; ?>
</main>

<?php include("../includes/footer.php"); ?>
