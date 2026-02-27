<?php

declare(strict_types=1);

namespace App\Config;

final class Config
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $jwtDisableExpiration = filter_var(
            $_ENV['JWT_DISABLE_EXPIRATION'] ?? 'false',
            FILTER_VALIDATE_BOOL
        );

        // Keep sessions long-lived by default and prevent accidental short expiries.
        $defaultJwtExpiration = 60 * 60 * 24 * 180; // 180 days
        $minimumJwtExpiration = (int)($_ENV['JWT_MIN_EXPIRATION'] ?? (60 * 60 * 24 * 90)); // 90 days

        $rawJwtExpiration = $_ENV['JWT_EXPIRATION'] ?? null;
        $configuredJwtExpiration = is_numeric($rawJwtExpiration)
            ? (int)$rawJwtExpiration
            : $defaultJwtExpiration;

        $jwtExpiration = $configuredJwtExpiration > 0
            ? max($configuredJwtExpiration, $minimumJwtExpiration)
            : $defaultJwtExpiration;

        $config = [
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here',
                'expiration' => $jwtExpiration,
                'disable_expiration' => $jwtDisableExpiration,
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
