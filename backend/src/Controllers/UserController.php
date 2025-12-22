<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Actions\LoginAction;
use App\Actions\RegisterAction;
use App\Actions\GetProfileAction;
use App\Actions\UpdateProfileAction;
use App\Config\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class UserController
{
    public function __construct(
        private readonly LoginAction $loginAction,
        private readonly RegisterAction $registerAction,
        private readonly GetProfileAction $getProfileAction,
        private readonly UpdateProfileAction $updateProfileAction
    ) {}

    public function getProfile(Request $request, Response $response): void
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            $profile = $this->getProfileAction->execute($userId);
            $response->success($profile);
        } catch (Exception $e) {
            $response->error($e->getMessage(), (int)($e->getCode() ?: 500));
        }
    }

    public function updateProfile(Request $request, Response $response): void
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            $data = $request->getBody();
            
            $profile = $this->updateProfileAction->execute($userId, $data);
            $response->success($profile, 'Profile updated successfully');

        } catch (Exception $e) {
            $response->error($e->getMessage(), (int)($e->getCode() ?: 500));
        }
    }

    public function register(Request $request, Response $response): void
    {
        try {
            $data = $request->getBody();
            
            // Validate required fields
            $required = ['username', 'email', 'password'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $response->error("Field '{$field}' is required", 400);
                    return;
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $response->error('Invalid email format', 400);
                return;
            }

            $user = $this->registerAction->execute($data);

            $response->withStatus(201)->success($user, 'Account created successfully! You received 500 welcome eggs.');

        } catch (Exception $e) {
            $response->error($e->getMessage(), (int)($e->getCode() ?: 500));
        }
    }

    public function login(Request $request, Response $response): void
    {
        try {
            $data = $request->getBody();
            
            if (!isset($data['email']) || !isset($data['password'])) {
                $response->error('Email and password are required', 400);
                return;
            }

            $result = $this->loginAction->execute((string)$data['email'], (string)$data['password']);

            $response->success($result, 'Login successful');

        } catch (Exception $e) {
            $response->error($e->getMessage(), (int)($e->getCode() ?: 401));
        }
    }

    private function getUserIdFromToken(Request $request): int
    {
        // Try to get from attribute first (set by middleware)
        $userId = $request->getAttribute('user_id');
        if ($userId) {
            return (int) $userId;
        }
        
        // Fallback to manual token handling if needed
        $token = (string)$request->getHeader('authorization');
        if ($token !== '' && preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            $token = $matches[1];
        } else {
            throw new Exception('Authorization token required', 401);
        }

        $secret = (string)Config::get('jwt.secret');
        
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (int) $decoded->user_id;
        } catch (Exception $e) {
            throw new Exception('Invalid token', 401);
        }
    }
}

