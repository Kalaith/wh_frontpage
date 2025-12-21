<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\UserRepository;
use Exception;

class UpdateProfileAction
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function execute(int $userId, array $data): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        $this->userRepository->update($userId, $data);

        $updated = $this->userRepository->findById($userId);
        return [
            'id' => $updated['id'],
            'username' => $updated['username'],
            'display_name' => $updated['display_name'],
            'role' => $updated['role'],
            'email' => $updated['email']
        ];
    }
}
