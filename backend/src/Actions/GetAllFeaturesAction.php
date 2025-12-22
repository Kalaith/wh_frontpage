<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\FeatureRequestRepository;

class GetAllFeaturesAction
{
    public function __construct(
        private readonly FeatureRequestRepository $featureRepo
    ) {}

    public function execute(array $filters = [], string $sortBy = 'total_eggs', string $sortDirection = 'desc', int $limit = 50): array
    {
        return $this->featureRepo->getByFilters($filters, $sortBy, $sortDirection, $limit);
    }
}
