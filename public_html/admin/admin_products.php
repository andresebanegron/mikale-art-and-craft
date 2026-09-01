<?php
include("../includes/auth.php");
include_once("../config/db.php");
include_once("../includes/csrf.php");
include("../includes/header_admin.php");

$res = $db->query("SELECT * FROM products ORDER BY id DESC");
?>

<div class="admin-container">
    <div class="page-header">
        <h2>Product Management</h2>
        <a href="add_product.php" class="btn btn-primary">Add New Product</a>
    </div>

    <div class="products-grid">
        <?php while($p = $res->fetch_assoc()): ?>
        <div class="product-admin-card">
            <div class="product-image">
                <?php if ($p['image']): ?>
                    <img src="/assets/uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                <?php else: ?>
                    <div class="no-image">No Image</div>
                <?php endif; ?>
            </div>
            <div class="product-details">
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <p class="product-price">$<?= number_format($p['price'], 2) ?></p>
                <p class="product-info">Category: <?= htmlspecialchars($p['category']) ?></p>
                <p class="product-info">Size: <?= htmlspecialchars($p['size']) ?></p>
                <p class="product-status <?= $p['active'] ? 'active' : 'inactive' ?>">
                    Status: <?= $p['active'] ? "Active" : "Disabled" ?>
                </p>
            </div>
            <div class="product-actions">
                <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-small">Edit</a>
                <?php if ($p['active']): ?>
                    <form method="POST" action="disable_product.php" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-small btn-warning">Disable</button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="active_product.php" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-small btn-success">Activate</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include("../includes/footer.php"); ?>

