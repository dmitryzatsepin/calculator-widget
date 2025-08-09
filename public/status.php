<?php
// status.php - проверка статуса сервера

header('Content-Type: application/json');

$status = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'php_version' => PHP_VERSION,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'query_string' => $_SERVER['QUERY_STRING'] ?? 'none',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?> 