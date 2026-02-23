<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DatabaseManager
{
    private static ?PDO $instance = null;

    public function __construct() {}

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = $_ENV['DB_HOST'] ?? throw new \RuntimeException('DB_HOST environment variable is not set');
        $db   = $_ENV['DB_DATABASE'] ?? throw new \RuntimeException('DB_DATABASE environment variable is not set');
        $user = $_ENV['DB_USERNAME'] ?? throw new \RuntimeException('DB_USERNAME environment variable is not set');
        $pass = $_ENV['DB_PASSWORD'] ?? throw new \RuntimeException('DB_PASSWORD environment variable is not set');
        $port = $_ENV['DB_PORT'] ?? throw new \RuntimeException('DB_PORT environment variable is not set');
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        self::$instance = new PDO($dsn, $user, $pass, $options);
        return self::$instance;
    }

    public function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }

    public function commit(): void
    {
        self::getConnection()->commit();
    }

    public function rollBack(): void
    {
        if (self::getConnection()->inTransaction()) {
            self::getConnection()->rollBack();
        }
    }

    public function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }
}
