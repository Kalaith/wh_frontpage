<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class AuthProxyController
{
    private string $authBaseUrl;

    public function __construct()
    {
        $this->authBaseUrl = rtrim($_ENV['AUTH_SERVICE_URL'] ?? ($_ENV['APP_AUTH_URL'] ?? ($_ENV['AUTH_API_URL'] ?? 'http://localhost:8001/api')), '/');
    }

    private function forward(string $path, array $body = [], string $method = 'POST', array $headers = []): array
    {
        $url = $this->authBaseUrl . $path;

        $defaultHeaders = ["Content-Type: application/json"];
        $allHeaders = array_merge($defaultHeaders, $headers);

        $opts = [
            "http" => [
                "method" => $method,
                "header" => implode("\r\n", $allHeaders) . "\r\n",
                "timeout" => 5,
                "ignore_errors" => true
            ]
        ];

        if ($method !== 'GET' && !empty($body)) {
            $opts["http"]["content"] = json_encode($body);
        }

        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            $error = error_get_last();
            return ['success' => false, 'error' => ['message' => 'Failed to contact Auth service', 'details' => $error['message'] ?? 'unknown']];
        }

        $decoded = json_decode($result, true);
        return $decoded ?? ['success' => false, 'error' => ['message' => 'Invalid response from auth service']];
    }

    public function login(Request $request, Response $response): void
    {
        $data = $request->getBody();
        $res = $this->forward('/auth/login', $data);

        if ($res['success']) {
            $response->success($res['data'] ?? $res);
        } else {
            $response->error($res['error']['message'] ?? 'Login failed', 400);
        }
    }

    public function register(Request $request, Response $response): void
    {
        $data = $request->getBody();
        $res = $this->forward('/auth/register', $data);

        if ($res['success']) {
            $response->withStatus(201)->success($res['data'] ?? $res);
        } else {
            $response->error($res['error']['message'] ?? 'Registration failed', 400);
        }
    }

    public function getCurrentUser(Request $request, Response $response): void
    {
        // Get the Authorization header
        $authHeader = $request->getHeader('authorization');
        if (!$authHeader) {
            $response->error('Authorization header missing', 401);
            return;
        }

        // Forward the request to auth service with the token
        $headers = ["Authorization: $authHeader"];
        $res = $this->forward('/auth/user', [], 'GET', $headers);

        if ($res['success']) {
            $response->success($res['data'] ?? $res);
        } else {
            $response->error($res['error']['message'] ?? 'Failed to fetch user', $res['error']['code'] ?? 401);
        }
    }
}
