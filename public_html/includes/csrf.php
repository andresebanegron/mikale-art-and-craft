<?php
require_once __DIR__ . '/session_security.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validate_csrf(?string $submittedToken = null): void
{
    $submittedToken ??= $_POST['csrf_token'] ?? null;
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submittedToken)
        || $sessionToken === ''
        || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(403);
        exit('Request validation failed.');
    }
}
