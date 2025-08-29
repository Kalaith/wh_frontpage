<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader) {
            return $this->unauthorizedResponse('Authorization header missing');
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Invalid authorization header format');
        }

        $token = $matches[1];
        
        try {
            $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here';
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));

            // Add user information to request attributes
            $request = $request
                ->withAttribute('user_id', $decoded->user_id)
                ->withAttribute('user_email', $decoded->email ?? '')
                ->withAttribute('user_role', $decoded->role ?? 'user')
                ->withAttribute('user_roles', [$decoded->role ?? 'user']);

            return $handler->handle($request);
            
        } catch (\Exception $e) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }
    }

    private function unauthorizedResponse(string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        $payload = json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => 401
            ]
        ]);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
