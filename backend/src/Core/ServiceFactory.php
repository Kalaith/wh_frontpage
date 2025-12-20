<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\UserController;
use App\Controllers\ProjectController;
use App\Controllers\TrackerController;
use RuntimeException;

final class ServiceFactory
{
    public function create(string $class): object
    {
        return match ($class) {
            UserController::class => new UserController(),
            ProjectController::class => new ProjectController(),
            TrackerController::class => new TrackerController(),
            default => throw new RuntimeException("Unknown class: $class")
        };
    }
}
