<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

$stripePublicKey = getenv('STRIPE_PUBLIC_KEY') ?: '';
$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: '';
$domain = rtrim((string)(getenv('DOMAIN') ?: ''), '/');

if ($stripeSecretKey === '') {
    error_log('Missing STRIPE_SECRET_KEY configuration.');
    http_response_code(500);
    exit('Payment configuration is unavailable.');
}

$stripe = new \Stripe\StripeClient([
    'api_key' => $stripeSecretKey,
    'stripe_version' => '2024-06-20',
]);
