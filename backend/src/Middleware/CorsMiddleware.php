<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        
        $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5173');
        $origin = $request->getHeaderLine('Origin');
        
        if (in_array($origin, $allowedOrigins)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        } else {
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }
        
        return $response
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}
