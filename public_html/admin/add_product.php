<?php
include("../includes/auth.php");
include("../includes/csrf.php");
include_once("../config/db.php");
include("../includes/upload_image.php");
include_once("../includes/security.php");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    $name = sanitize($_POST['name'] ?? '');
    $priceRaw = $_POST['price'] ?? '';
    $category = sanitize($_POST['category'] ?? '');
    $size = sanitize($_POST['size'] ?? '');
    $personalization = sanitize($_POST['personalization'] ?? '');

    if (!validate_string($name, 255) || !validate_price($priceRaw)) {
        $error = 'Please enter a valid product name and price.';
    } elseif (mb_strlen($category, 'UTF-8') > 255 || mb_strlen($size, 'UTF-8') > 255 || mb_strlen($personalization, 'UTF-8') > 255) {
        $error = 'One or more product fields are too long.';
    } else {
        $imageName = '';
        try {
            if (!empty($_FILES['image']['name'])) {
                $imageName = upload_product_image($_FILES['image']);
            }

            $price = (float)$priceRaw;
            $stmt = $db->prepare("INSERT INTO products
                (name, price, category, size, personalization, image, active)
                VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('sdssss', $name, $price, $category, $size, $personalization, $imageName);
            $stmt->execute();

            header("Location: admin_products.php");
            exit;
        } catch (Throwable $e) {
            if ($imageName !== '') {
                $path = __DIR__ . '/../assets/uploads/' . $imageName;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            error_log('Add product failed: ' . $e->getMessage());
            $error = 'Unable to add the product. Please try again.';
        }
    }
}

include("../includes/header_admin.php");
?>

<?php if ($error): ?>
<div class="error-message"><?= escape($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="card">
<input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">

<input name="name" placeholder="Product name" maxlength="255" required value="<?= escape($_POST['name'] ?? '') ?>">
<input name="price" placeholder="Price" required type="number" min="0" step="0.01" value="<?= escape($_POST['price'] ?? '') ?>">

<input name="category" placeholder="Category" maxlength="255" value="<?= escape($_POST['category'] ?? '') ?>">
<input name="size" placeholder="Size" maxlength="255" value="<?= escape($_POST['size'] ?? '') ?>">
<label>Required personalization label (leave blank if none):</label>
<input type="text" name="personalization" maxlength="255" placeholder="e.g. Engraving text" value="<?= escape($_POST['personalization'] ?? '') ?>">

<input type="file" name="image" accept="image/jpeg,image/png,image/webp">

<button>Add Product</button>
</form>
