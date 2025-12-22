<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\UserRepository;
use App\Config\Config;
use Firebase\JWT\JWT;
use Exception;

class LoginAction
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function execute(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid credentials', 401);
        }

        // Generate JWT token
        $secret = Config::get('jwt.secret');
        $expiration = Config::get('jwt.expiration');
        
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + $expiration
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'display_name' => $user['display_name'] ?? $user['username'],
                'egg_balance' => (int)($user['egg_balance'] ?? 0)
            ],
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', $payload['exp'])
        ];
    }
}
