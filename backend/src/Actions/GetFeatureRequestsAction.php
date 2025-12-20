<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureRequest;

class GetFeatureRequestsAction
{
    public function execute(array $filters = [], string $sortBy = 'votes', string $sortDirection = 'desc', ?int $limit = null): array
    {
        return (array)FeatureRequest::getByFilters(
            array_filter($filters), 
            $sortBy, 
            $sortDirection, 
            $limit
        );
    }
}
