<?php
// start secure session on all public pages that include this header
include_once __DIR__ . '/session_security.php';
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
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/assets/style.css">
  <title>Mikale Art and Craft</title>
</head>
<body>
<header>
  <div class="container">
    <h2>Mikale Art and Craft</h2>
    <nav>
      <a class="btn" href="/home">Home</a>
      <a class="btn" href="/">Products</a>
      <a class="btn" href="/track">Track Order</a>
      <a class="btn" href="/cart">Cart</a>
    </nav>
  </div>
</header>