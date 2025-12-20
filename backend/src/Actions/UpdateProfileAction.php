<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Exception;

class UpdateProfileAction
{
    public function execute(int $userId, array $data): array
    {
        $user = User::find($userId);
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        // Update allowed fields
        $allowedFields = ['display_name', 'username'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                // Check if username is unique
                if ($field === 'username' && $data[$field] !== $user->username) {
                    $existingUser = User::where('username', $data[$field])->where('id', '!=', $userId)->first();
                    if ($existingUser) {
                        throw new Exception('Username already taken', 400);
                    }
                }
                
                $user->{$field} = $data[$field];
            }
        }

        $user->save();
        return (array)$user->toApiArray();
    }
}
