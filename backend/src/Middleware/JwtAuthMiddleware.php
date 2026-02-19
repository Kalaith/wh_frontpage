<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Config\Config;
use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthMiddleware
{
    public function handle(Request $request, Response $response): bool
    {
        $token = $request->getHeader('authorization');
        
        if (!$token || !preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            $response->error('Authorization header missing or invalid', 401);
            return false;
        }

        $token = $matches[1];
        $secret = Config::get('jwt.secret');
        
        try {
            // Allow minor server/client clock skew.
            JWT::$leeway = 60;

            // Validate local JWT token
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $payload = (array) $decoded;

            // Look up user in database using repository
            $userId = (int) $payload['user_id'];
            
            // Create PDO and repository
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $db = $_ENV['DB_DATABASE'] ?? 'frontpage';
            $dbUser = $_ENV['DB_USERNAME'] ?? 'root';
            $pass = $_ENV['DB_PASSWORD'] ?? '';
            $port = $_ENV['DB_PORT'] ?? '3306';
            
            $pdo = new \PDO(
                "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
                $dbUser,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]
            );
            
            $userRepo = new UserRepository($pdo);
            $user = $userRepo->findById($userId);
            
            if (!$user) {
                $response->error('User not found', 401);
                return false;
            }

            // Add user information to request attributes
            $request->setAttribute('user_id', $user['id']);
            $request->setAttribute('user_email', $user['email']);
            $request->setAttribute('user_name', $user['display_name'] ?? $user['username']);
            $request->setAttribute('user_role', $user['role']);
            $request->setAttribute('jwt_payload', $payload);

            return true;
            
        } catch (\Exception $e) {
            error_log('JWT Auth Middleware Error: ' . $e->getMessage());
            $response->error('Invalid or expired token: ' . $e->getMessage(), 401);
            return false;
        }
    }
}
