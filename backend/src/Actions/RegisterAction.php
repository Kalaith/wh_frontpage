<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\UserRepository;
use App\Config\Config;
use Firebase\JWT\JWT;
use Exception;

class RegisterAction
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function execute(array $data): array
    {
        // Check if user already exists
        if ($this->userRepository->findByEmail($data['email'])) {
            throw new Exception('Email already registered', 400);
        }

        // Create user
        $id = $this->userRepository->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'display_name' => $data['display_name'] ?? $data['username'],
            'role' => 'user'
        ]);

        $user = $this->userRepository->findById($id);

        // Generate JWT token for auto-login
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

        // Return same format as login for auto-login
        return [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'display_name' => $user['display_name'] ?? $user['username'],
                'egg_balance' => (int)($user['egg_balance'] ?? 500)
            ],
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', $payload['exp'])
        ];
    }
}

