<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
file_put_contents(getenv('RPC_TEST_HTTP_LOG'), json_encode(['method' => $method, 'path' => $path, 'body' => file_get_contents('php://input')]) . "\n", FILE_APPEND);
header('Allow: POST, OPTIONS');
http_response_code($method === 'POST' && $path === '/reject' ? 403 : 200);
echo '{}';
