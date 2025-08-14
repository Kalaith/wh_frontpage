<?php
// router.php - Router script for PHP built-in server

// If the requested file exists, serve it directly
if (file_exists(__DIR__ . '/public' . $_SERVER['REQUEST_URI'])) {
    return false; // Let the built-in server handle static files
}

// Otherwise, route everything through index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/public/index.php';
