<?php
include("../includes/auth.php");
include_once("../config/db.php");
include("../includes/header_admin.php");

$res = $db->query("SELECT * FROM orders ORDER BY created_at DESC");
?>

<div class="admin-container">
    <div class="page-header">
        <h2>Order Management</h2>
    </div>

    <div class="orders-list">
        <?php while ($o = $res->fetch_assoc()): ?>
        <div class="order-card">
            <div class="order-header">
                <h3><?= htmlspecialchars($o['customer_name']) ?></h3>
                <span class="order-status status-<?= strtolower($o['status']) ?>">
                    <?= htmlspecialchars($o['status']) ?>
                </span>
            </div>
            <div class="order-details">
                <p><strong>Email:</strong> <?= htmlspecialchars($o['email']) ?></p>
                <p><strong>Tracking Code:</strong> <?= htmlspecialchars($o['tracking_code']) ?></p>
                <p><strong>Total:</strong> $<?= number_format($o['total'], 2) ?></p>
                <p><strong>Order Date:</strong> <?= date('M j, Y g:i A', strtotime($o['created_at'])) ?></p>
            </div>
            <div class="order-actions">
                <a href="view_order.php?id=<?= $o['id'] ?>" class="btn">View Details</a>
                <a href="view_order.php?id=<?= $o['id'] ?>" class="btn btn-primary">Update Status</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include("../includes/footer.php"); ?>

