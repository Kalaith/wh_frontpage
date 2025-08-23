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
        $method = strtoupper($request->getMethod());
        $origin = $request->getHeaderLine('Origin');

        // Read allowed origins from environment (comma-separated). Default is localhost dev origin.
        $allowedOriginsRaw = $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5173';
        $allowedOrigins = array_map('trim', explode(',', $allowedOriginsRaw));

        // Decide Access-Control-Allow-Origin value
        $allowOrigin = '*';
        if ($origin) {
            if (in_array('*', $allowedOrigins, true)) {
                $allowOrigin = '*';
            } elseif (in_array($origin, $allowedOrigins, true)) {
                $allowOrigin = $origin;
            } else {
                // Origin not allowed; we'll still return a response but without CORS headers
                $allowOrigin = null;
            }
        }

        // Build allowed headers list: prefer explicit env setting, else echo requested headers + common ones
        $requestedHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        $envHeaders = $_ENV['CORS_ALLOWED_HEADERS'] ?? '';
        if ($envHeaders) {
            $allowHeaders = $envHeaders;
        } elseif ($requestedHeaders) {
            $allowHeaders = $requestedHeaders . ', Authorization, Content-Type';
        } else {
            $allowHeaders = 'Content-Type, Authorization, X-Requested-With, X-Init-Key';
        }

        $allowMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';

        // For preflight OPTIONS requests, return early with the CORS headers
        if ($method === 'OPTIONS') {
            $response = new \Slim\Psr7\Response(200);
            if ($allowOrigin !== null) {
                $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin);
                // When using wildcard origin, credentials cannot be true
                if ($allowOrigin === '*') {
                    $response = $response->withHeader('Access-Control-Allow-Credentials', 'false');
                } else {
                    $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
                }
                $response = $response->withHeader('Access-Control-Allow-Methods', $allowMethods)
                                     ->withHeader('Access-Control-Allow-Headers', $allowHeaders)
                                     ->withHeader('Vary', 'Origin')
                                     ->withHeader('Cache-Control', 'no-store');
            }
            return $response;
        }

        // For non-OPTIONS, proceed to handler and then append CORS headers to the response
        $response = $handler->handle($request);
        if ($allowOrigin !== null) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin)
                                 ->withHeader('Access-Control-Allow-Methods', $allowMethods)
                                 ->withHeader('Access-Control-Allow-Headers', $allowHeaders)
                                 ->withHeader('Access-Control-Allow-Credentials', ($allowOrigin === '*' ? 'false' : 'true'))
                                 ->withHeader('Vary', 'Origin');
        }

        return $response;
    }
}
