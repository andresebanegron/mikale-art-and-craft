<?php
include_once __DIR__ . '/../includes/session_security.php';
include_once __DIR__ . '/../includes/security.php';
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once __DIR__ . '/../includes/csrf.php';
    validate_csrf();

    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    if ($product_id <= 0) {
        header("Location: /");
        exit;
    }

    if ($quantity < 1) {
        header("Location: /");
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        die("Product not found");
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }

    header("Location: /");
    exit;
}
 
include_once __DIR__ . '/../includes/header_public.php';
?>

<div class="cart-container">
    <h2>Your Cart</h2>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart">
            <p>Your cart is empty.</p>
            <a href="/" class="btn">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php
            $total = 0;
            foreach ($_SESSION['cart'] as $cart_key => $qty):
                $parts = explode('::', $cart_key, 2);
                $product_id = (int)$parts[0];
                $custom_name = isset($parts[1]) ? base64_decode($parts[1]) : '';

                $stmt = $db->prepare("SELECT name, price, image, personalization FROM products WHERE id = ?");
                $stmt->bind_param('i', $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();

                if (!$product) continue;

                $personalizationLabel = trim($product['personalization']);
                if ($personalizationLabel === 'plaque_required' || $personalizationLabel === 'plaque_optional') {
                    $personalizationLabel = 'Plaque name';
                }

                $subtotal = $product['price'] * $qty;
                $total += $subtotal;
            ?>
            <div class="cart-item">
                <div class="cart-item-image">
                    <?php if (!empty($product['image'])): ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div class="no-image">No Image</div>
                    <?php endif; ?>
                </div>
                <div class="cart-item-details">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="cart-price">$<?= number_format($product['price'], 2) ?> each</p>
                    <p class="cart-quantity">Quantity: <?= intval($qty) ?></p>
                    <?php if ($custom_name !== ''): ?>
                        <p class="cart-custom"><?= htmlspecialchars($personalizationLabel ?: 'Personalization') ?>: <?= htmlspecialchars($custom_name) ?></p>
                    <?php endif; ?>
                    <p class="cart-subtotal">Subtotal: $<?= number_format($subtotal, 2) ?></p>
                </div>
                <div class="cart-item-actions">
                    <form method="POST" action="/remove_from_cart">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="product_key" value="<?= htmlspecialchars($cart_key) ?>">
                        <label>Remove quantity:</label>
                        <input type="number" name="quantity" min="1" max="<?= intval($qty) ?>" value="1">
                        <button type="submit" class="btn btn-danger">Remove</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <div class="cart-total">
                <h3>Total: $<?= number_format($total, 2) ?></h3>
            </div>
            <div class="cart-actions">
                <a href="/" class="btn">Continue Shopping</a>
                <a href="/checkout" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>