<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\FeatureRequestRepository;
use App\Repositories\UserRepository;
use App\Repositories\ActivityFeedRepository;
use App\Repositories\EggTransactionRepository;
use Exception;

class CreateFeatureAction
{
    public function __construct(
        private readonly FeatureRequestRepository $featureRepo,
        private readonly ActivityFeedRepository $activityRepo,
        private readonly UserRepository $userRepo,
        private readonly EggTransactionRepository $eggRepo
    ) {}

    public function execute(array $data): array
    {
        $user = $this->userRepo->findById($data['user_id']);
        if (!$user) {
            throw new Exception('User not found', 400);
        }

        $balance = $this->eggRepo->getBalanceForUser($data['user_id']);
        if ($balance < 100) {
            throw new Exception('Insufficient eggs. Creating a feature request costs 100 eggs.', 400);
        }

        $id = $this->featureRepo->create([
            'user_id' => $data['user_id'],
            'project_id' => $data['project_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'] ?? null,
            'use_case' => $data['use_case'] ?? null,
            'expected_benefits' => $data['expected_benefits'] ?? null,
            'priority_level' => $data['priority_level'] ?? 'medium',
            'feature_type' => $data['feature_type'] ?? 'enhancement',
            'tags' => isset($data['tags']) ? $data['tags'] : null,
            'status' => 'pending'
        ]);

        $feature = $this->featureRepo->findById($id);

        // Deduct eggs from user
        $this->eggRepo->create([
            'user_id' => $data['user_id'],
            'amount' => -100,
            'type' => 'spend',
            'description' => "Created feature request: {$feature['title']}",
            'reference_id' => $id,
            'reference_type' => 'feature_request'
        ]);

        $this->activityRepo->create([
            'activity_type' => 'feature_request_created',
            'message' => "New feature request: {$feature['title']}",
            'reference_id' => $id,
            'reference_type' => 'feature_request',
            'user_id' => $data['user_id']
        ]);

        return $feature;
    }
}
