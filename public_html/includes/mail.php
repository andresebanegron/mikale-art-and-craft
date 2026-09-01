<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Send email using PHPMailer
 */
function sendMail($to, $subject, $body, $isHtml = true) {
    try {
        $mail = new PHPMailer(true);
        
        // SMTP configuration from environment
        $mail->isSMTP();
        $mail->Host       = getenv('MAIL_HOST') ?: 'localhost';
        $mail->Port       = getenv('MAIL_PORT') ?: 587;
        $mail->SMTPAuth   = (getenv('MAIL_AUTH') === 'true');
        $mail->Username   = getenv('MAIL_USERNAME') ?: '';
        $mail->Password   = getenv('MAIL_PASSWORD') ?: '';
        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
        
        // Set from address
        $fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@mikaleartcraft.com';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Mikale Art & Craft';
        $mail->setFrom($fromEmail, $fromName);
        
        // Add recipient
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send order creation notification to admin
 */
function sendOrderCreationNotificationToAdmin($order) {
    $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@mikaleartcraft.com';
    
    $subject = "New Order Created - Tracking Code: " . $order['tracking_code'];
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .order-info { background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .label { font-weight: bold; color: #555; }
            .divider { border-top: 1px solid #ddd; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>New Order Received</h2>
        </div>
        <div class='content'>
            <div class='order-info'>
                <p><span class='label'>Order ID:</span> {$order['id']}</p>
                <p><span class='label'>Tracking Code:</span> {$order['tracking_code']}</p>
                <p><span class='label'>Customer Name:</span> {$order['customer_name']}</p>
                <p><span class='label'>Customer Email:</span> {$order['email']}</p>
                <p><span class='label'>Delivery Address:</span> {$order['address']}</p>
                <p><span class='label'>Total Amount:</span> \${$order['total']}</p>
                <p><span class='label'>Status:</span> {$order['status']}</p>
                <p><span class='label'>Order Date:</span> {$order['created_at']}</p>
            </div>
            <div class='divider'></div>
            <p>Please review this order in your admin dashboard and update the status as needed.</p>
        </div>
    </body>
    </html>
    ";
    
    return sendMail($adminEmail, $subject, $body, true);
}

/**
 * Send order delivered notification to customer with tracking code
 */
function sendOrderDeliveredNotificationToCustomer($order) {
    $subject = "Your Order Has Been Delivered - Tracking Code: " . $order['tracking_code'];
    
    $trackingUrl = getenv('APP_URL') ?: 'http://localhost/public';
    $trackingUrl = rtrim($trackingUrl, '/') . '/track';
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .order-info { background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .label { font-weight: bold; color: #555; }
            .tracking-code { 
                background-color: #e7f3ff; 
                padding: 15px; 
                border-left: 4px solid #007bff; 
                margin: 20px 0;
                font-family: monospace;
                font-size: 18px;
            }
            .divider { border-top: 1px solid #ddd; margin: 20px 0; }
            .button { 
                display: inline-block; 
                background-color: #007bff; 
                color: white; 
                padding: 10px 20px; 
                text-decoration: none; 
                border-radius: 5px; 
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>✓ Your Order Has Been Delivered!</h2>
        </div>
        <div class='content'>
            <p>Hello {$order['customer_name']},</p>
            <p>Great news! Your order has been delivered. Here are your order details:</p>
            
            <div class='order-info'>
                <p><span class='label'>Order Tracking Code:</span></p>
                <div class='tracking-code'>{$order['tracking_code']}</div>
                <p><span class='label'>Delivery Address:</span> {$order['address']}</p>
                <p><span class='label'>Total Amount:</span> \${$order['total']}</p>
                <p><span class='label'>Delivery Date:</span> " . date('M d, Y') . "</p>
            </div>
            
            <div class='divider'></div>
            <p>You can track your order anytime using the tracking code above:</p>
            <a href='{$trackingUrl}' class='button'>Track Your Order</a>
            
            <div class='divider'></div>
            <p>Thank you for your purchase!</p>
            <p>Best regards,<br>Mikale Art & Craft Team</p>
        </div>
    </body>
    </html>
    ";
    
    return sendMail($order['email'], $subject, $body, true);
}
