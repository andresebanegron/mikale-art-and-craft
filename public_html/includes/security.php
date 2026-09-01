<?php
function sanitize($data): string
{
    // Normalize input only. Escape for the output context when rendering.
    return trim((string) $data);
}

function validate_string($str, $max = 255): bool
{
    $str = (string) $str;
    return $str !== '' && mb_strlen($str, 'UTF-8') <= $max;
}

function validate_email($email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_int($num): bool
{
    return filter_var($num, FILTER_VALIDATE_INT) !== false;
}

function validate_price($price): bool
{
    $value = filter_var($price, FILTER_VALIDATE_FLOAT);
    return $value !== false && $value >= 0;
}

function escape($data): string
{
    return htmlspecialchars((string) $data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
