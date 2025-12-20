<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\FeatureRequest;
use App\Models\FeatureVote;
use App\Models\EggTransaction;
use Exception;

class GetProfileAction
{
    public function execute(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        $profile = $user->toApiArray();
        
        $profile['stats'] = [
            'features_created' => FeatureRequest::where('user_id', $userId)->count(),
            'votes_cast' => FeatureVote::where('user_id', $userId)->count(),
            'eggs_spent' => (int)EggTransaction::where('user_id', $userId)->where('amount', '<', 0)->sum('amount') * -1,
            'eggs_earned' => (int)EggTransaction::where('user_id', $userId)->where('amount', '>', 0)->sum('amount'),
            'features_approved' => FeatureRequest::where('user_id', $userId)->where('status', 'approved')->count(),
            'features_completed' => FeatureRequest::where('user_id', $userId)->where('status', 'completed')->count(),
        ];

        return (array)$profile;
    }
}
