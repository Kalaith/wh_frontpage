<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Exception;

class RegisterAction
{
    public function execute(array $data): array
    {
        // Check if user already exists
        if (User::where('email', $data['email'])->exists()) {
            throw new Exception('Email already registered', 400);
        }

        if (User::where('username', $data['username'])->exists()) {
            throw new Exception('Username already taken', 400);
        }

        // Create user
        $user = User::createUser([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'display_name' => $data['display_name'] ?? $data['username']
        ]);

        return (array)$user->toApiArray();
    }
}
