<?php
include_once __DIR__ . '/security.php';

$scriptDir = str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME']));
$assetBase = preg_replace('#/(public|admin|admin-portal)$#', '', $scriptDir);
if ($assetBase === '' || $assetBase === '/') {
    $assetBase = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Mikale Art and Craft</title>
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/assets/style.css">
</head>
<body>

<header>
  <div class="container">
    <h2>Admin Panel – Mikale Art and Craft</h2>
    <nav>
      <a class="btn" href="dashboard.php">Dashboard</a>
      <a class="btn" href="admin_orders.php">Orders</a>
      <a class="btn" href="admin_products.php">Products</a>
      <a class="btn" href="logout.php">Logout</a>
    </nav>
  </div>
</header>

<div class="container">
