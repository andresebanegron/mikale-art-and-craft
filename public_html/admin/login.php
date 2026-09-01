<?php
require '../config/db.php';
require '../includes/csrf.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = $db->prepare("SELECT id, username, password FROM admins WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        header("Location: /admin-portal/dashboard.php");
        exit;
    } else {
        $message = "Invalid username or password";
    }
}
?>

<?php
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
    <title>Admin Login - Mikale Art and Craft</title>
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/assets/style.css">
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <h2>Admin Login</h2>
        <p class="login-subtitle">Access the Mikale Art and Craft admin panel</p>

        <?php if ($message): ?>
            <div class="error-message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin-portal/" class="login-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</div>

</body>
</html>
