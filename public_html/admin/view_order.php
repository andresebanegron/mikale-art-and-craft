<?php
include("../includes/auth.php");
include_once("../config/db.php");
include("../includes/header_admin.php");
include("../includes/csrf.php");

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid order id");
}

/*
|--------------------------------------------------------------------------
| GET ORDER INFORMATION (MySQLi version)
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found");
}

/*
|--------------------------------------------------------------------------
| GET ORDER ITEMS (MySQLi version)
|--------------------------------------------------------------------------
*/
$customTextColumn = false;
$colResult = $db->query("SHOW COLUMNS FROM order_items LIKE 'custom_text'");
if ($colResult && $colResult->num_rows > 0) {
    $customTextColumn = true;
}

$sql = "SELECT p.name, oi.quantity, p.price";
if ($customTextColumn) {
    $sql .= ", oi.custom_text";
}
$sql .= ", p.personalization";
$sql .= " FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$items = $stmt->get_result();
?>

<div class="admin-container">
    <div class="page-header">
        <h2>Order Details</h2>
        <a href="admin_orders.php" class="btn">Back to Orders</a>
    </div>

    <div class="order-details-card">
        <h3>Customer Information</h3>
        <div class="order-info-grid">
            <div class="info-item">
                <strong>Customer:</strong>
                <?= htmlspecialchars($order['customer_name']) ?>
            </div>

            <div class="info-item">
                <strong>Email:</strong>
                <?= htmlspecialchars($order['email']) ?>
            </div>

            <div class="info-item">
                <strong>Address:</strong>
                <?= htmlspecialchars($order['address']) ?>
            </div>

            <div class="info-item">
                <strong>Payment Method:</strong>
                <?= htmlspecialchars($order['payment_method']) ?>
            </div>

            <div class="info-item">
                <strong>Tracking Code:</strong>
                <?= htmlspecialchars($order['tracking_code']) ?>
            </div>

            <div class="info-item">
                <strong>Status:</strong>
                <span class="order-status status-<?= strtolower($order['status']) ?>">
                    <?= htmlspecialchars($order['status']) ?>
                </span>
            </div>

            <div class="info-item">
                <strong>Order Date:</strong>
                <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
            </div>
        </div>
    </div>

    <div class="order-products-card">
        <h3>Order Items</h3>

        <div class="order-items">
            <?php $total = 0; ?>

            <?php while ($i = $items->fetch_assoc()): ?>
                <?php
                $lineTotal = $i['price'] * $i['quantity'];
                $total += $lineTotal;
                ?>

                <div class="order-item">
                    <div class="item-details">
                        <h4><?= htmlspecialchars($i['name']) ?></h4>
                        <p>
                            Quantity: <?= (int)$i['quantity'] ?>
                            × $<?= number_format($i['price'], 2) ?> each
                        </p>
                        <?php if (!empty($i['custom_text'] ?? '')): ?>
                            <?php
                                $label = trim((string)($i['personalization'] ?? ''));
                                if ($label === 'plaque_required' || $label === 'plaque_optional') {
                                    $label = 'Plaque name';
                                }
                            ?>
                            <p><strong><?= htmlspecialchars($label ?: 'Personalization') ?>:</strong> <?= htmlspecialchars($i['custom_text']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="item-total">
                        $<?= number_format($lineTotal, 2) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="order-total">
            <h3>Total: $<?= number_format($total, 2) ?></h3>
        </div>
    </div>

    <div class="order-actions-card">
        <h3>Update Order Status</h3>

        <form method="POST" action="update_order_status.php">
            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <input type="hidden"
                   name="order_id"
                   value="<?= $id ?>">

            <div class="form-group">
                <label for="status">New Status:</label>

                <select name="status" id="status" required>
                    <option value="Pending"
                        <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>
                        Pending
                    </option>

                    <option value="Processing"
                        <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>
                        Processing
                    </option>

                    <option value="Shipped"
                        <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>
                        Shipped
                    </option>

                    <option value="Delivered"
                        <?= $order['status'] === 'Delivered' ? 'selected' : '' ?>>
                        Delivered
                    </option>

                    <option value="Cancelled"
                        <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>
                        Cancelled
                    </option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                Update Status
            </button>
        </form>
    </div>
</div>

<?php
$stmt->close();
include("../includes/footer.php");
?>
