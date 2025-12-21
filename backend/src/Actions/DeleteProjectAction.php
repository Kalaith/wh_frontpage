<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\ProjectRepository;

class DeleteProjectAction
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {}

    public function execute(int $id): bool
    {
        return $this->projectRepository->delete($id);
    }
}
