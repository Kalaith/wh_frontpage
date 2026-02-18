<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Request;
use App\Core\Response;
use Exception;

final class Router
{
    private array $routes = [];
    private string $basePath = '';
    private array $globalMiddleware = [];

    public function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function addMiddleware(callable|string $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function put(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middleware): void
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = "#^" . $pattern . "$#";
        
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = explode('?', $uri)[0];

        if (!empty($this->basePath) && strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath));
        }
        
        if (empty($path)) {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                $routeParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $request = new Request($routeParams);
                $response = new Response();

                // Run Middleware
                $middlewares = array_merge($this->globalMiddleware, $route['middleware']);
                foreach ($middlewares as $mw) {
                    $mwInstance = is_string($mw) ? new $mw() : $mw;
                    $result = $mwInstance->handle($request, $response);
                    
                    if ($result === false) {
                        return;
                    }
                }

                $handler = $route['handler'];
                if (is_callable($handler)) {
                    $handler($request, $response);
                    return;
                }

                if (is_array($handler)) {
                    $factory = new ServiceFactory();
                    $controller = $factory->create($handler[0]);
                    $methodName = $handler[1];

                    $method = new \ReflectionMethod($controller, $methodName);
                    $requiredParams = $method->getNumberOfRequiredParameters();
                    if ($requiredParams >= 3 || $method->getNumberOfParameters() >= 3) {
                        $controller->$methodName($request, $response, $routeParams);
                    } else {
                        $controller->$methodName($request, $response);
                    }
                    return;
                }
            }
        }

        (new Response())->withStatus(404)->json(['error' => 'Route not found: ' . $path]);
    }
}
