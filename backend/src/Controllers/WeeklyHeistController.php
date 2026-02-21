<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\WeeklyHeistRepository;

class WeeklyHeistController
{
    private WeeklyHeistRepository $repo;

    public function __construct(WeeklyHeistRepository $repo)
    {
        $this->repo = $repo;
    }

    public function current(Request $request, Response $response): void
    {
        $heist = $this->repo->getActive();

        if (!$heist) {
            $response->success(null);
            return;
        }

        $response->success($heist->toArray());
    }
}
