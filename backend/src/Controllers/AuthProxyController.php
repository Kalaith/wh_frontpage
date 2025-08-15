<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
                "timeout" => 5
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

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $res = $this->forward('/auth/login', $data);

        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($res['success'] ? 200 : 400);
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $res = $this->forward('/auth/register', $data);

        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($res['success'] ? 201 : 400);
    }

    public function getCurrentUser(Request $request, Response $response): Response
    {
        // Get the Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $payload = json_encode(['success' => false, 'error' => ['message' => 'Authorization header missing', 'code' => 401]]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Forward the request to auth service with the token
        $headers = ["Authorization: $authHeader"];
        $res = $this->forward('/auth/user', [], 'GET', $headers);

        $payload = json_encode($res);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($res['success'] ? 200 : ($res['error']['code'] ?? 400));
    }
}
