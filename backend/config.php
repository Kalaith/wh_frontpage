<?php
// config.php

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

return [
    'app' => [
        'environment' => getenv('APP_ENV') ?: $_ENV['APP_ENV'] ?? 'production',
    ],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here',
        'expiration' => (int)(getenv('JWT_EXPIRATION') ?: $_ENV['JWT_EXPIRATION'] ?? 86400), // 24 hours default
    ]
];
