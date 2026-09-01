<?php
include("../includes/auth.php");
include("../includes/csrf.php");
include_once("../config/db.php");
include("../includes/upload_image.php");
include_once("../includes/security.php");

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid product.');
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

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
        $oldImage = (string)($product['image'] ?? '');
        $imageName = $oldImage;
        $newImageName = null;

        try {
            if (!empty($_FILES['image']['name'])) {
                $newImageName = upload_product_image($_FILES['image']);
                $imageName = $newImageName;
            }

            $price = (float)$priceRaw;
            $stmt = $db->prepare("UPDATE products SET
                    name = ?, price = ?, category = ?, size = ?, personalization = ?, image = ?
                WHERE id = ?");
            $stmt->bind_param('sdssssi', $name, $price, $category, $size, $personalization, $imageName, $id);
            $stmt->execute();

            if ($newImageName !== null && $oldImage !== '' && $oldImage !== $newImageName) {
                $oldPath = __DIR__ . '/../assets/uploads/' . basename($oldImage);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            header("Location: admin_products.php");
            exit;
        } catch (Throwable $e) {
            if ($newImageName !== null) {
                $newPath = __DIR__ . '/../assets/uploads/' . $newImageName;
                if (is_file($newPath)) {
                    @unlink($newPath);
                }
            }
            error_log('Edit product failed: ' . $e->getMessage());
            $error = 'Unable to update the product. Please try again.';
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

<input name="name" maxlength="255" value="<?= escape($_POST['name'] ?? $product['name']) ?>" required>
<input name="price" min="0" value="<?= escape($_POST['price'] ?? $product['price']) ?>" required type="number" step="0.01">
<input name="category" maxlength="255" value="<?= escape($_POST['category'] ?? $product['category']) ?>">
<input name="size" maxlength="255" value="<?= escape($_POST['size'] ?? $product['size']) ?>">
<label>Required personalization label (leave blank if none):</label>
<input type="text" name="personalization" maxlength="255" placeholder="e.g. Engraving text" value="<?= escape($_POST['personalization'] ?? $product['personalization']) ?>">

<?php if (!empty($product['image'])): ?>
    <img src="../assets/uploads/<?= escape($product['image']) ?>" width="80" alt="Current product image"><br>
<?php endif; ?>

<input type="file" name="image" accept="image/jpeg,image/png,image/webp">
<button>Update Product</button>
</form>
