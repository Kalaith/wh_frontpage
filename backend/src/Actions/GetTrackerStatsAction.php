<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\ProjectRepository;
use App\Repositories\FeatureRequestRepository;
use App\Repositories\ProjectSuggestionRepository;

class GetTrackerStatsAction
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly FeatureRequestRepository $featureRequestRepository,
        private readonly ProjectSuggestionRepository $suggestionRepository
    ) {}

    public function execute(): array
    {
        return [
            'projects' => [
                'total' => $this->projectRepository->count()
            ],
            'feature_requests' => $this->featureRequestRepository->getStats(),
            'suggestions' => $this->suggestionRepository->getStats()
        ];
    }
}
