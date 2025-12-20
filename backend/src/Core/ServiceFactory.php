<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\UserController;
use App\Controllers\ProjectController;
use App\Controllers\TrackerController;
use App\Controllers\AdminController;
use App\Controllers\AuthProxyController;
use App\Controllers\FeatureRequestController;
use App\Controllers\ProjectHealthController;
use App\Controllers\ProjectNewsFeedController;
use App\Controllers\ProjectUpdateController;
use RuntimeException;

final class ServiceFactory
{
    public function create(string $class): object
    {
        return match ($class) {
            UserController::class => new UserController(),
            ProjectController::class => new ProjectController(),
            TrackerController::class => new TrackerController(),
            AdminController::class => new AdminController(),
            AuthProxyController::class => new AuthProxyController(),
            FeatureRequestController::class => new FeatureRequestController(),
            ProjectHealthController::class => new ProjectHealthController(),
            ProjectNewsFeedController::class => new ProjectNewsFeedController(),
            ProjectUpdateController::class => new ProjectUpdateController(),
            default => throw new RuntimeException("Unknown class: $class")
        };
    }
}
