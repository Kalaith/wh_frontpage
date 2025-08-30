<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Services\Auth0Service;
use App\Models\User;

class JwtAuthMiddleware implements MiddlewareInterface
{
    private Auth0Service $auth0Service;
    
    public function __construct()
    {
        $this->auth0Service = new Auth0Service();
    }

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
            // Validate Auth0 JWT token
            $payload = $this->auth0Service->validateToken($token);
            $userInfo = $this->auth0Service->extractUserInfo($payload);

            // Look up user in database to get role
            $user = User::where('auth0_id', $userInfo['sub'])->first();
            $userRole = $user ? $user->role : 'user'; // Default to 'user' if not found in DB
            
            // Add Auth0 user information to request attributes
            $request = $request
                ->withAttribute('auth0_sub', $userInfo['sub'])
                ->withAttribute('user_email', $userInfo['email'])
                ->withAttribute('user_name', $userInfo['name'])
                ->withAttribute('user_role', $userRole) // Add the missing user_role attribute
                ->withAttribute('user_id', $user ? $user->id : null)
                ->withAttribute('auth0_payload', $payload)
                ->withAttribute('auth0_user_info', $userInfo);

            return $handler->handle($request);
            
        } catch (\Exception $e) {
            error_log('JWT Auth Middleware Error: ' . $e->getMessage());
            return $this->unauthorizedResponse('Invalid or expired token: ' . $e->getMessage());
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
