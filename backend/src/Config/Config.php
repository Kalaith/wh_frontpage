<?php

declare(strict_types=1);

namespace App\Config;

final class Config
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = [
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here',
                // Default to 90 days for long-lived sessions unless overridden by JWT_EXPIRATION.
                'expiration' => (int)($_ENV['JWT_EXPIRATION'] ?? 7776000),
            ],
            'app' => [
                'env' => $_ENV['APP_ENV'] ?? 'production',
            ]
        ];

        $parts = explode('.', $key);
        $value = $config;

        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}
