<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Config\Config;
use App\Repositories\UserRepository;
use App\Repositories\DatabaseManager;
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
            
            // Create repository using DatabaseManager
            $db = DatabaseManager::getConnection();
            $userRepo = new UserRepository($db);
            $user = $userRepo->findById($userId);
            
            if (!$user) {
                $response->error('User not found', 401);
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
            $response->error('Invalid or expired token: ' . $e->getMessage(), 401);
            return false;
        }
    }
}
