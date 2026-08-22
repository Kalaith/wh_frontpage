<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Config\Config;
use App\Repositories\UserRepository;
use App\Repositories\DatabaseManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtAuthMiddleware
{
    public function handle(Request $request, Response $response): bool
    {
        $token = $request->getHeader('authorization');
        
        if (!$token || !preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            $this->unauthorized($response, 'Authorization header missing or invalid');
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
            
            // Create repository using DatabaseManager
            $db = DatabaseManager::getConnection();
            $userRepo = new UserRepository($db);
            $user = $userRepo->findById($userId);
            
            if (!$user) {
                $this->unauthorized($response, 'User not found');
                return false;
            }

            // Add user information to request attributes
            $request->setAttribute('user_id', (int)$user['id']);
            $request->setAttribute('user_email', $user['email']);
            $request->setAttribute('user_name', $user['display_name'] ?? $user['username']);
            $request->setAttribute('user_role', $user['role']);
            $request->setAttribute('jwt_payload', $payload);

            return true;
            
        } catch (\Exception $e) {
            error_log('JWT Auth Middleware Error: ' . $e->getMessage());
            $this->unauthorized($response, 'Invalid or expired token');
            return false;
        }
    }

    private function unauthorized(Response $response, string $message): void
    {
        $response->withStatus(401)->json([
            'success' => false,
            'message' => $message,
            'error' => 'Authentication required',
            'login_url' => $this->requiredLoginUrl(),
        ]);
    }

    private function requiredLoginUrl(): string
    {
        $loginUrl = trim((string) ($_ENV['WEBHATCHERY_LOGIN_URL'] ?? ''));
        if ($loginUrl === '') {
            throw new \RuntimeException('WEBHATCHERY_LOGIN_URL environment variable is not set');
        }

        return $loginUrl;
    }
}
