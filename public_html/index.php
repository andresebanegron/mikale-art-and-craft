<?php
// session handling and security includes
include_once __DIR__ . '/includes/session_security.php';
include_once __DIR__ . '/includes/security.php';
include_once __DIR__ . '/includes/csrf.php';
require __DIR__ . '/config/db.php';
include("includes/header_public.php");

$q = isset($_GET['q']) ? sanitize(trim($_GET['q'])) : '';
$category = isset($_GET['category']) ? sanitize(trim($_GET['category'])) : '';
$size = isset($_GET['size']) ? sanitize(trim($_GET['size'])) : '';
$order = isset($_GET['order']) ? $_GET['order'] : ''; // order key will be validated later

$order_map = [
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC'
];

$order_sql = isset($order_map[$order]) ? $order_map[$order] : 'id ASC';

// Get distinct categories and sizes for the filters
$cats_result = $db->query("SELECT DISTINCT category FROM products WHERE active = 1 ORDER BY category");
$categories = [];
while ($row = $cats_result->fetch_row()) {
    $categories[] = $row[0];
}
$sizes_result = $db->query("SELECT DISTINCT size FROM products WHERE active = 1 ORDER BY size");
$sizes = [];
while ($row = $sizes_result->fetch_row()) {
    $sizes[] = $row[0];
}

// Build WHERE and params safely
$where = "active = 1 AND name LIKE ?";
$params = ["%$q%"]; 
$types = 's';
if ($category !== '') {
    $where .= " AND category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($size !== '') {
    $where .= " AND size = ?";
    $params[] = $size;
    $types .= 's';
}

$sql = "SELECT * FROM products WHERE $where ORDER BY $order_sql";
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

function getPersonalizationLabel($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    if ($value === 'plaque_required' || $value === 'plaque_optional') {
        return 'Plaque name';
    }
    return $value;
}
?>

<h3>Products</h3>

<form method="get" action="/" class="search-form">
    <input type="text" name="q" placeholder="Search products by name" value="<?= htmlspecialchars($q) ?>">
    <select name="category">
        <option value="">All categories</option>
        <?php foreach($categories as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="size">
        <option value="">All sizes</option>
        <?php foreach($sizes as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>" <?= $size === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="order">
        <option value="">Default</option>
        <option value="price_asc" <?= $order === 'price_asc' ? 'selected' : '' ?>>Price: Low &uarr;</option>
        <option value="price_desc" <?= $order === 'price_desc' ? 'selected' : '' ?>>Price: High &darr;</option>
    </select>
    <button type="submit">Search</button>
</form>

<div class="clearfix">
<div class="main-content">
    <div class="products-grid">
        <?php while($product = $result->fetch_assoc()): ?>
        <div class="product-card">
            <img src="assets/uploads/<?= escape($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <div class="product-info">
                <h4><?= htmlspecialchars($product['name']) ?></h4>
                <p class="price">$<?= number_format((float)$product['price'], 2) ?></p>
                <p class="details">Category: <?= htmlspecialchars($product['category']) ?></p>
                <p class="details">Size: <?= htmlspecialchars($product['size']) ?></p>
                <?php $personalizationLabel = getPersonalizationLabel($product['personalization']); ?>
                <p class="details">Personalization: <?= $personalizationLabel ? htmlspecialchars($personalizationLabel) : 'None' ?></p>
                <form method="POST" action="/add_to_cart">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                    <div class="product-form-row">
                        <input type="number" name="quantity" min="1" value="1">
                        <button type="submit" class="btn">Add to Cart</button>
                    </div>

                    <?php if ($personalizationLabel): ?>
                        <div class="product-custom-name">
                            <label for="custom_name_<?= (int)$product['id'] ?>"><?= htmlspecialchars($personalizationLabel) ?>:</label>
                            <input id="custom_name_<?= (int)$product['id'] ?>" name="custom_name" required placeholder="Enter <?= htmlspecialchars(strtolower($personalizationLabel)) ?>">
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="sidebar">
    <h3>Cart</h3>
    <?php
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $cart_key => $qty) {
            $parts = explode('::', $cart_key, 2);
            $product_id = (int)$parts[0];
            $custom_name = isset($parts[1]) ? base64_decode($parts[1]) : '';
            $stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $display = $product['name'] . ($custom_name !== '' ? ' - ' . htmlspecialchars($custom_name) : '');
            echo "<div class='cart-item'>Product: {$display} - Qty: $qty</div>";
        }
        echo "<br><a href='/cart'><button class='btn'>View Cart</button></a>";
    } else {
        echo "Cart is empty.";
    }
    ?>
</div>
</div>
<script>
// Poll admin-managed personalization values and update product cards live
(function() {
    const apiUrl = '/public/api/get_personalizations.php';
    const pollInterval = 5000;

    function getProductIds() {
        const inputs = document.querySelectorAll('form[action="/add_to_cart"] input[name="product_id"]');
        return Array.from(inputs).map(i => parseInt(i.value, 10)).filter(Boolean);
    }

    function updateDom(items) {
        Object.keys(items).forEach(id => {
            const labelText = (items[id] || '').trim();
            const inputId = 'custom_name_' + id;
            const existingInput = document.getElementById(inputId);
            const form = document.querySelector('form[action="/add_to_cart"] input[name="product_id"][value="' + id + '"]')?.closest('form');

            if (!labelText) {
                if (existingInput) {
                    const wrapper = existingInput.closest('.product-custom-name');
                    if (wrapper) wrapper.remove();
                }
                return;
            }

            const label = (labelText === 'plaque_required' || labelText === 'plaque_optional') ? 'Plaque name' : labelText;

            if (existingInput) {
                const lbl = document.querySelector('label[for="' + inputId + '"]');
                if (lbl) lbl.textContent = label + ':';
                existingInput.placeholder = 'Enter ' + label.toLowerCase();
                existingInput.required = true;
                return;
            }

            if (form) {
                const div = document.createElement('div');
                div.className = 'product-custom-name';
                const lbl = document.createElement('label');
                lbl.setAttribute('for', inputId);
                lbl.textContent = label + ':';
                const input = document.createElement('input');
                input.id = inputId;
                input.name = 'custom_name';
                input.required = true;
                input.placeholder = 'Enter ' + label.toLowerCase();
                div.appendChild(lbl);
                div.appendChild(input);
                form.appendChild(div);
            }
        });
    }

    async function poll() {
        try {
            const ids = getProductIds();
            if (ids.length === 0) return;
            const res = await fetch(apiUrl + '?ids=' + ids.join(','), {cache: 'no-store'});
            if (!res.ok) return;
            const data = await res.json();
            updateDom(data);
        } catch (e) {
            console.warn('personalization poll error', e);
        }
    }

    setInterval(poll, pollInterval);
    setTimeout(poll, 1000);
})();
</script>

<?php include("includes/footer.php"); ?>