<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureRequest;
use App\Models\User;
use Exception;

class CreateFeatureAction
{
    public function execute(array $data): array
    {
        $user = User::find($data['user_id']);
        if (!$user) {
            throw new Exception('User not found', 400);
        }

        if ($user->egg_balance < 100) {
            throw new Exception('Insufficient eggs. Creating a feature request costs 100 eggs.', 400);
        }

        $feature = FeatureRequest::create([
            'user_id' => $data['user_id'],
            'project_id' => $data['project_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'] ?? null,
            'use_case' => $data['use_case'] ?? null,
            'expected_benefits' => $data['expected_benefits'] ?? null,
            'priority_level' => $data['priority_level'] ?? 'medium',
            'feature_type' => $data['feature_type'] ?? 'enhancement',
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            'status' => 'pending'
        ]);

        // Deduct eggs from user
        $user->spendEggs(100, "Created feature request: {$feature->title}", $feature->id, 'feature_request');

        return (array)$feature->toApiArray();
    }
}
