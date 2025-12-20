<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $params;
    private array $queryParams;
    private array $body;
    private array $headers;
    private array $attributes = [];

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->queryParams = $_GET;
        $this->body = $this->parseBody();
        $this->headers = $this->getHeaders();
    }

    public function getParam(string $name, mixed $default = null): mixed
    {
        return $this->params[$name] ?? $this->queryParams[$name] ?? $default;
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->queryParams[$name] ?? $default;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    private function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (str_contains($contentType, 'application/json')) {
            $input = @file_get_contents('php://input');
            return json_decode((string)$input, true) ?? [];
        }

        return $_POST;
    }

    private function getHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower($name)] = $value;
            }
        }

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif ($key === 'CONTENT_TYPE') {
                $headers['content-type'] = $value;
            } elseif ($key === 'CONTENT_LENGTH') {
                $headers['content-length'] = $value;
            } elseif ($key === 'REDIRECT_HTTP_AUTHORIZATION') {
                $headers['authorization'] = $value;
            }
        }

        return $headers;
    }
}
