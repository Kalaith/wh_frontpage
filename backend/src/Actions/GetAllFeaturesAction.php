<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureRequest;

class GetAllFeaturesAction
{
    public function execute(array $filters = [], string $sortBy = 'total_eggs', string $sortDirection = 'desc', int $limit = 50): array
    {
        $query = FeatureRequest::query();

        if (($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['project_id']) && $filters['project_id']) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['category']) && $filters['category']) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        if ($limit > 0) {
            $query->limit($limit);
        }

        $query->with(['user:id,username,display_name', 'project:id,title']);

        return $query->get()->map(fn($feature) => $feature->toApiArray())->toArray();
    }
}
