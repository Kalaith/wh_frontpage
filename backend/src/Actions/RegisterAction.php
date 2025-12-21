<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\UserRepository;
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

        // Add proper check for username uniqueness in UserRepository if needed
        // For now, assuming create might fail if there's a unique constraint

        // Create user
        $id = $this->userRepository->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'display_name' => $data['display_name'] ?? $data['username'],
            'role' => 'user'
        ]);

        $user = $this->userRepository->findById($id);

        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'display_name' => $user['display_name']
        ];
    }
}
