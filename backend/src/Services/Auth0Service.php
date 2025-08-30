<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\JWK;
use GuzzleHttp\Client;

class Auth0Service
{
    private string $domain;
    private string $audience;
    private string $clientId;
    private array $jwksCache = [];
    private int $jwksCacheTime = 0;
    private int $jwksCacheDuration = 3600; // 1 hour
    
    public function __construct()
    {
        $this->domain = $_ENV['AUTH0_DOMAIN'] ?? '';
        $this->audience = $_ENV['AUTH0_AUDIENCE'] ?? '';
        $this->clientId = $_ENV['AUTH0_CLIENT_ID'] ?? '';
        
        if (empty($this->domain) || empty($this->audience)) {
            throw new \Exception('Auth0 configuration missing. Set AUTH0_DOMAIN and AUTH0_AUDIENCE environment variables.');
        }
    }
    
    /**
     * Validate an Auth0 JWT token
     */
    public function validateToken(string $token): array
    {
        try {
            // Get JWKS
            $jwks = $this->getJwks();
            
            // Set leeway for clock skew (5 minutes)
            JWT::$leeway = 300;
            
            // Decode and validate token
            $decoded = JWT::decode($token, JWK::parseKeySet($jwks));
            
            // Convert to array
            $payload = (array) $decoded;
            
            // Validate audience - accept both API audience (access tokens) and client ID (ID tokens)
            $tokenAudience = $payload['aud'] ?? [];
            if (is_string($tokenAudience)) {
                $tokenAudience = [$tokenAudience];
            }
            
            $validAudiences = [$this->audience];
            if (!empty($this->clientId)) {
                $validAudiences[] = $this->clientId;
            }
            
            $audienceValid = false;
            foreach ($validAudiences as $validAudience) {
                if (in_array($validAudience, $tokenAudience)) {
                    $audienceValid = true;
                    break;
                }
            }
            
            if (!$audienceValid) {
                error_log('Token audience validation failed. Token audiences: ' . json_encode($tokenAudience) . ', Valid audiences: ' . json_encode($validAudiences));
                throw new \Exception('Invalid audience');
            }
            
            // Validate issuer
            $expectedIssuer = "https://{$this->domain}/";
            if (($payload['iss'] ?? '') !== $expectedIssuer) {
                throw new \Exception('Invalid issuer');
            }
            
            return $payload;
            
        } catch (\Exception $e) {
            error_log('Token validation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Extract user information from token payload
     */
    public function extractUserInfo(array $payload): array
    {
        return [
            'sub' => $payload['sub'] ?? '',
            'email' => $payload['email'] ?? '',
            'name' => $payload['name'] ?? '',
            'nickname' => $payload['nickname'] ?? '',
            'picture' => $payload['picture'] ?? '',
            'email_verified' => $payload['email_verified'] ?? false,
            'updated_at' => $payload['updated_at'] ?? null
        ];
    }
    
    /**
     * Get provider from Auth0 sub claim
     */
    public function getProviderFromSub(string $sub): string
    {
        // Auth0 sub format: provider|provider_user_id
        $parts = explode('|', $sub, 2);
        return $parts[0] ?? 'auth0';
    }
    
    /**
     * Get provider user ID from Auth0 sub claim
     */
    public function getProviderUserIdFromSub(string $sub): string
    {
        // Auth0 sub format: provider|provider_user_id
        $parts = explode('|', $sub, 2);
        return $parts[1] ?? $sub;
    }
    
    private function getJwks(): array
    {
        $now = time();
        
        // Check cache
        if (!empty($this->jwksCache) && ($now - $this->jwksCacheTime) < $this->jwksCacheDuration) {
            return $this->jwksCache;
        }
        
        try {
            $client = new Client([
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'Frontpage-Backend/1.0'
                ],
                // Disable SSL verification for development
                'verify' => $_ENV['APP_ENV'] === 'production'
            ]);
            
            $response = $client->get("https://{$this->domain}/.well-known/jwks.json");
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to fetch JWKS: HTTP ' . $response->getStatusCode());
            }
            
            $jwks = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JWKS JSON: ' . json_last_error_msg());
            }
            
            // Cache the result
            $this->jwksCache = $jwks;
            $this->jwksCacheTime = $now;
            
            return $jwks;
            
        } catch (\Exception $e) {
            error_log('Failed to fetch JWKS from ' . $this->domain . ': ' . $e->getMessage());
            throw $e;
        }
    }
}