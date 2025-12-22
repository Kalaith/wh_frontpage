<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class AuthProxyController
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(Request $request, Response $response): void
    {
        $data = $request->getBody();
        $res = $this->authService->forward('/auth/login', $data);

        if (isset($res['success']) && $res['success']) {
            $response->success($res['data'] ?? $res);
        } else {
            $response->error($res['error']['message'] ?? 'Login failed', 400);
        }
    }

    public function register(Request $request, Response $response): void
    {
        $data = $request->getBody();
        $res = $this->authService->forward('/auth/register', $data);

        if (isset($res['success']) && $res['success']) {
            $response->withStatus(201)->success($res['data'] ?? $res);
        } else {
            $response->error($res['error']['message'] ?? 'Registration failed', 400);
        }
    }

    public function getCurrentUser(Request $request, Response $response): void
    {
        // Get the Authorization header
        $authHeader = (string)$request->getHeader('authorization');
        if ($authHeader === '') {
            $response->error('Authorization header missing', 401);
            return;
        }

        // Forward the request to auth service with the token
        $headers = ["Authorization: $authHeader"];
        $res = $this->authService->forward('/auth/user', [], 'GET', $headers);

        if (isset($res['success']) && $res['success']) {
            $response->success($res['data'] ?? $res);
        } else {
            $response->error($res['error']['message'] ?? 'Failed to fetch user', (int)($res['error']['code'] ?? 401));
        }
    }
}
