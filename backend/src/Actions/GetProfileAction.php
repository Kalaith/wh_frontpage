<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\UserRepository;
use App\Repositories\FeatureRequestRepository;
use App\Repositories\FeatureVoteRepository;
use App\Repositories\EggTransactionRepository;
use Exception;

class GetProfileAction
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly FeatureRequestRepository $featureRepo,
        private readonly FeatureVoteRepository $voteRepo,
        private readonly EggTransactionRepository $eggRepo
    ) {}

    public function execute(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'] ?? $user['username'],
            'role' => $user['role'],
            'email' => $user['email'],
            'egg_balance' => (int)($user['egg_balance'] ?? 0),
            'stats' => [
                'features_created' => $this->featureRepo->countByUser($userId),
                'votes_cast' => $this->voteRepo->countByUser($userId),
                'eggs_spent' => $this->eggRepo->getSpentForUser($userId),
                'eggs_earned' => $this->eggRepo->getEarnedForUser($userId),
                'features_approved' => $this->featureRepo->countByUserAndStatus($userId, 'approved'),
                'features_completed' => $this->featureRepo->countByUserAndStatus($userId, 'completed'),
            ]
        ];
    }
}
