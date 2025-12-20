<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Config\Config;
use Firebase\JWT\JWT;
use Exception;

class LoginAction
{
    public function execute(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();
        
        if (!$user || !password_verify($password, $user->password_hash)) {
            throw new Exception('Invalid credentials', 401);
        }

        // Generate JWT token
        $secret = Config::get('jwt.secret');
        $expiration = Config::get('jwt.expiration');
        
        $payload = [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + $expiration
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return [
            'user' => $user->toApiArray(),
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', $payload['exp'])
        ];
    }
}
