<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\FeatureRequestRepository;
use App\Repositories\ActivityFeedRepository;

class CreateFeatureRequestAction
{
    public function __construct(
        private readonly FeatureRequestRepository $featureRepo,
        private readonly ActivityFeedRepository $activityRepo
    ) {}

    public function execute(array $data): array
    {
        $id = $this->featureRepo->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'Open',
            'priority' => $data['priority'] ?? 'Medium',
            'type' => $data['type'] ?? 'Feature',
            'project_id' => !empty($data['project_id']) ? (int)$data['project_id'] : null,
            'user_id' => $data['user_id']
        ]);

        $feature = $this->featureRepo->findById($id);

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
