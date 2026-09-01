<?php
include("../includes/auth.php");
include("../includes/header_admin.php");

/**
 * Helper function to return a COUNT(*) result using MySQLi
 */
function getCount($db, $sql)
{
    $result = $db->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();
    return (int)$row['count'];
}
?>

<div class="dashboard-container">
    <h2>Admin Dashboard</h2>
    <p>Welcome to the Mikale Art and Craft administration panel.</p>

    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Products</h3>
            <p class="stat-number">
                <?= getCount($db, "SELECT COUNT(*) AS count FROM products") ?>
            </p>
            <p>Total products in catalog</p>
        </div>

        <div class="stat-card">
            <h3>Active Products</h3>
            <p class="stat-number">
                <?= getCount($db, "SELECT COUNT(*) AS count FROM products WHERE active = 1") ?>
            </p>
            <p>Currently available</p>
        </div>

        <div class="stat-card">
            <h3>Orders</h3>
            <p class="stat-number">
                <?= getCount($db, "SELECT COUNT(*) AS count FROM orders") ?>
            </p>
            <p>Total orders placed</p>
        </div>

        <div class="stat-card">
            <h3>Pending Orders</h3>
            <p class="stat-number">
                <?= getCount($db, "SELECT COUNT(*) AS count FROM orders WHERE status = 'Pending'") ?>
            </p>
            <p>Awaiting processing</p>
        </div>
    </div>

    <div class="dashboard-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="add_product.php" class="btn btn-primary">Add New Product</a>
            <a href="admin_products.php" class="btn">Manage Products</a>
            <a href="admin_orders.php" class="btn">View Orders</a>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
