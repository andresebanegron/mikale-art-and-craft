<?php
/**
 * Database bootstrap.
 *
 * Development can load values from ../private/.env. In production, prefer
 * server-level environment variables and keep secrets outside the web root.
 */
function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        // Allow simple quoted values in local .env files.
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

loadEnvFile(__DIR__ . '/../../private/.env');

$host = getenv('DB_HOST') ?: '';
$dbName = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$port = (int) (getenv('DB_PORT') ?: 3306);

$missing = [];
foreach (['DB_HOST' => $host, 'DB_NAME' => $dbName, 'DB_USER' => $user] as $key => $value) {
    if ($value === '') {
        $missing[] = $key;
    }
}

if ($missing) {
    error_log('Missing database configuration: ' . implode(', ', $missing));
    http_response_code(500);
    exit('Application configuration error.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli($host, $user, $pass, $dbName, $port);
    $db->set_charset('utf8mb4');
} catch (Throwable $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to connect to the application database.');
}
