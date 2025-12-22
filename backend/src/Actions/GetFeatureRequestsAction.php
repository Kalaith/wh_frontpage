<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\FeatureRequestRepository;

class GetFeatureRequestsAction
{
    public function __construct(
        private readonly FeatureRequestRepository $featureRepo
    ) {}

    public function execute(array $filters = [], string $sortBy = 'vote_count', string $sortDirection = 'desc', ?int $limit = null): array
    {
        return $this->featureRepo->getByFilters(
            $filters, 
            $sortBy, 
            $sortDirection, 
            $limit
        );
    }
}
