<?php

declare(strict_types=1);

namespace App\Services;

class AuthService
{
    private string $authBaseUrl;

    public function __construct()
    {
        $this->authBaseUrl = rtrim($_ENV['AUTH_SERVICE_URL'] ?? ($_ENV['APP_AUTH_URL'] ?? ($_ENV['AUTH_API_URL'] ?? 'http://localhost:8001/api')), '/');
    }

    public function forward(string $path, array $body = [], string $method = 'POST', array $headers = []): array
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
            $opts["http"]["content"] = (string)json_encode($body);
        }

        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            $error = error_get_last();
            return [
                'success' => false, 
                'error' => [
                    'message' => 'Failed to contact Auth service', 
                    'details' => $error['message'] ?? 'unknown'
                ]
            ];
        }

        $decoded = json_decode($result, true);
        return (array)($decoded ?? [
            'success' => false, 
            'error' => ['message' => 'Invalid response from auth service']
        ]);
    }
}
