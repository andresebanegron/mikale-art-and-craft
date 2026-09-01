<?php
include("../includes/auth.php");
include("../includes/csrf.php");
include_once("../config/db.php");
include_once __DIR__ . "/../includes/security.php";
include_once __DIR__ . "/../includes/mail.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    $id = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    $allowed = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (!in_array($status, $allowed)) {
        die("Invalid status");
    }

    // Get order details before updating
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    // Update order status
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();

    // Send email notification if status changed to Delivered
    if ($status === 'Delivered' && $order) {
        sendOrderDeliveredNotificationToCustomer($order);
    }

    header("Location: view_order.php?id=$id");
    exit;
}

// If no POST data or wrong method, redirect to orders page
header("Location: admin_orders.php");
exit;
