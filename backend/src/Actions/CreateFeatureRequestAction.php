<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureRequest;
use App\Models\ActivityFeed;

class CreateFeatureRequestAction
{
    public function execute(array $data): array
    {
        // Process tags if provided
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_map('trim', explode(',', $data['tags']));
        }

        $featureRequest = FeatureRequest::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'] ?? 'Enhancement',
            'priority' => $data['priority'] ?? 'Medium',
            'tags' => $data['tags'] ?? null,
            'project_id' => !empty($data['project_id']) ? (int)$data['project_id'] : null,
            'submitted_by' => $data['submitted_by'] ?? 'anonymous'
        ]);

        // Log activity
        ActivityFeed::logActivity(
            'feature_request',
            'created',
            'New feature request submitted',
            $featureRequest->title,
            $featureRequest->id,
            'feature_request',
            $featureRequest->submitted_by
        );

        return (array)$featureRequest->toApiArray();
    }
}
