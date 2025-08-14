<?php
// index.php - Redirect to Slim application
// This file redirects all requests to the proper Slim application

// Redirect all requests to the Slim application
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/apps/auth/backend/public';

// Construct the new URL
$newUrl = $basePath . $requestUri;

// For API endpoints, redirect to the Slim app
if (strpos($requestUri, '/api/') === 0) {
    header("Location: $newUrl");
    exit;
}

// For other requests, show a simple message
?>
<!DOCTYPE html>
<html>
<head>
    <title>Auth API</title>
</head>
<body>
    <h1>Auth API</h1>
    <p>This is the Auth API backend. All API endpoints are available at:</p>
    <ul>
        <li><code>POST /api/auth/login</code> - User login</li>
        <li><code>POST /api/auth/register</code> - User registration</li>
        <li><code>GET /api/auth/me</code> - Get current user</li>
        <li><code>GET /api/users</code> - Get all users (admin only)</li>
        <li><code>GET /api/health</code> - Health check</li>
    </ul>
</body>
</html>
