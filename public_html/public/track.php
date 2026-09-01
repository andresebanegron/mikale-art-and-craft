<?php
include_once __DIR__ . '/../includes/session_security.php';
include_once __DIR__ . '/../includes/security.php';
include_once __DIR__ . '/../includes/csrf.php';
include("../config/db.php");
include("../includes/header_public.php");

// Get lost package contact info from environment or use defaults
$lostPackageContact = [
    'name' => getenv('LOST_PACKAGE_CONTACT_NAME') ?: 'Customer Support',
    'email' => getenv('LOST_PACKAGE_CONTACT_EMAIL') ?: 'support@mikaleartcraft.com',
    'phone' => getenv('LOST_PACKAGE_CONTACT_PHONE') ?: '+1 (555) 000-0000',
    'hours' => getenv('LOST_PACKAGE_CONTACT_HOURS') ?: 'Monday - Friday, 9 AM - 5 PM EST'
];

if ($_POST) {
    validate_csrf();
    $code = sanitize($_POST['code']);
    if (!validate_string($code, 100)) {
        die("Bad tracking code");
    }
    // Use a prepared statement with mysqli
    $stmt = $db->prepare("SELECT * FROM orders WHERE tracking_code = ?");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $o = $result->fetch_assoc();
        $status = htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8');
        $orderDate = htmlspecialchars($o['created_at'], ENT_QUOTES, 'UTF-8');
        $total = htmlspecialchars($o['total'], ENT_QUOTES, 'UTF-8');
        
        echo "<div class='card'>\n";
        echo "<h3>Order Details</h3>\n";
        echo "<div style='margin: 15px 0;'>\n";
        echo "<p><strong>Status:</strong> <span style='color: #007bff; font-weight: bold;'>{$status}</span></p>\n";
        echo "<p><strong>Order Date:</strong> {$orderDate}</p>\n";
        echo "<p><strong>Total Amount:</strong> \${$total}</p>\n";
        echo "</div>\n";
        
        // Show lost package information
        echo "<div style='background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 15px; margin-top: 20px;'>\n";
        echo "<h4 style='color: #856404; margin-top: 0;'>📦 Package Not Received?</h4>\n";
        echo "<p style='color: #856404;'>If your package has not arrived or appears to be lost, please contact us immediately:</p>\n";
        echo "<div style='background-color: white; padding: 12px; border-radius: 4px; margin: 10px 0;'>\n";
        echo "<p style='margin: 8px 0;'><strong>Contact Information:</strong></p>\n";
        echo "<p style='margin: 5px 0;'><strong>Name:</strong> " . htmlspecialchars($lostPackageContact['name']) . "</p>\n";
        echo "<p style='margin: 5px 0;'><strong>Email:</strong> <a href='mailto:" . htmlspecialchars($lostPackageContact['email']) . "'>" . htmlspecialchars($lostPackageContact['email']) . "</a></p>\n";
        echo "<p style='margin: 5px 0;'><strong>Phone:</strong> <a href='tel:" . htmlspecialchars($lostPackageContact['phone']) . "'>" . htmlspecialchars($lostPackageContact['phone']) . "</a></p>\n";
        echo "<p style='margin: 5px 0;'><strong>Business Hours:</strong> " . htmlspecialchars($lostPackageContact['hours']) . "</p>\n";
        echo "</div>\n";
        echo "<p style='color: #856404; font-size: 14px; margin-bottom: 0;'><strong>Please include your tracking code in your communication.</strong></p>\n";
        echo "</div>\n";
        
        echo "</div>";
    } else {
        echo "<div class='card' style='border-color: #dc3545; background-color: #f8d7da;'>";
        echo "<p style='color: #721c24;'><strong>Order not found</strong></p>";
        echo "<p style='color: #721c24; font-size: 14px;'>Please check your tracking code and try again.</p>";
        echo "</div>";
    }
}
?>

<form method="POST" class="card">
<h3>Track Your Order</h3>
<p style="color: #666; font-size: 14px;">Enter your tracking code to view order status and delivery information.</p>
<input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
<input name="code" placeholder="Tracking Code (e.g., ORD-XXXXXXXX)" required style="margin-bottom: 10px;">
<button>Track Order</button>
</form>

<?php include("../includes/footer.php"); ?>
