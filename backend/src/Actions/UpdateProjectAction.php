<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\ProjectRepository;

class UpdateProjectAction
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {}

    public function execute(int $id, array $data): ?array
    {
        $project = $this->projectRepository->findById($id);
        if (!$project) {
            return null;
        }

        $this->projectRepository->update($id, $data);

        return $this->projectRepository->findById($id);
    }
}
