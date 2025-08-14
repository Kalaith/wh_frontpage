<?php
// debug-jwt.php - Debug JWT token validation
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database.php';

use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get token from command line argument or prompt
$token = $argv[1] ?? null;

if (!$token) {
    echo "Usage: php debug-jwt.php <token>\n";
    echo "Or provide the token when prompted:\n";
    echo "Token: ";
    $token = trim(fgets(STDIN));
}

if (!$token) {
    echo "No token provided. Exiting.\n";
    exit(1);
}

echo "=== JWT Token Debug ===\n";
echo "Token (first 50 chars): " . substr($token, 0, 50) . "...\n\n";

// Try to decode the token
try {
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here';
    echo "JWT Secret (first 20 chars): " . substr($jwtSecret, 0, 20) . "...\n\n";
    
    $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
    
    echo "=== Token Decoded Successfully ===\n";
    echo "User ID: " . ($decoded->user_id ?? 'N/A') . "\n";
    echo "Email: " . ($decoded->email ?? 'N/A') . "\n";
    echo "Role: " . ($decoded->role ?? 'N/A') . "\n";
    echo "Roles: " . json_encode($decoded->roles ?? []) . "\n";
    echo "Issued At: " . ($decoded->iat ?? 'N/A') . " (" . ($decoded->iat ? date('Y-m-d H:i:s', $decoded->iat) : 'N/A') . ")\n";
    echo "Expires: " . ($decoded->exp ?? 'N/A') . " (" . ($decoded->exp ? date('Y-m-d H:i:s', $decoded->exp) : 'N/A') . ")\n";
    
    // Check if token is expired
    $now = time();
    if (isset($decoded->exp) && $decoded->exp < $now) {
        echo "\n❌ TOKEN IS EXPIRED!\n";
        echo "Current time: " . date('Y-m-d H:i:s', $now) . " ($now)\n";
        echo "Token expires: " . date('Y-m-d H:i:s', $decoded->exp) . " ({$decoded->exp})\n";
        echo "Expired " . ($now - $decoded->exp) . " seconds ago\n";
    } else {
        echo "\n✅ Token is not expired\n";
        echo "Time remaining: " . ($decoded->exp - $now) . " seconds\n";
    }
    
    // Check if user exists and is active
    if (isset($decoded->user_id)) {
        echo "\n=== User Validation ===\n";
        $user = User::find($decoded->user_id);
        
        if (!$user) {
            echo "❌ User with ID {$decoded->user_id} not found in database\n";
        } else {
            echo "✅ User found: {$user->email}\n";
            echo "Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
            echo "Roles: " . json_encode($user->getRoleNames()) . "\n";
            echo "Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
            
            if (!$user->is_active) {
                echo "❌ User is deactivated\n";
            }
        }
    }
    
} catch (Firebase\JWT\ExpiredException $e) {
    echo "❌ TOKEN EXPIRED: " . $e->getMessage() . "\n";
} catch (Firebase\JWT\SignatureInvalidException $e) {
    echo "❌ INVALID SIGNATURE: " . $e->getMessage() . "\n";
} catch (Firebase\JWT\BeforeValidException $e) {
    echo "❌ TOKEN NOT YET VALID: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ TOKEN VALIDATION ERROR: " . $e->getMessage() . "\n";
    echo "Error type: " . get_class($e) . "\n";
}

echo "\n=== Debug Complete ===\n";
