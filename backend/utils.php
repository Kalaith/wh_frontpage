<?php
// utils.php

function send_json($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    
    // Add debug information in development
    if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
        $data['debug'] = [
            'timestamp' => date('c'),
            'memory_usage' => memory_get_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ];
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function getBearerToken() {
    // Try different methods to get the authorization header
    $headers = null;
    
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    } elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        // Fallback for servers without apache_request_headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
    }
    
    // Look for Authorization header (case-insensitive)
    if ($headers) {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                if (preg_match('/Bearer\s+(.+)$/i', $value, $matches)) {
                    return $matches[1];
                }
            }
        }
    }
    
    // Also check $_SERVER directly
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

function log_debug($message, $data = null) {
    if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
        if ($data !== null) {
            $logMessage .= ' - ' . json_encode($data);
        }
        error_log($logMessage);
    }
}
