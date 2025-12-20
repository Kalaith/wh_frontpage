<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureRequest;
use App\Models\ProjectSuggestion;
use App\Models\Project;

class GetTrackerStatsAction
{
    public function execute(): array
    {
        return [
            'projects' => [
                'total' => Project::count()
            ],
            'feature_requests' => FeatureRequest::getStats(),
            'suggestions' => ProjectSuggestion::getStats()
        ];
    }
}
