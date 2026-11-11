<?php
// Only serves the public snapshot. Never scan the occurrence database on a web request.
$path = dirname(__DIR__, 2) . '/neon/cache/sample-catalog.json';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
if (!is_file($path)) {
    http_response_code(503);
    header('Cache-Control: no-store');
    echo json_encode(array('error' => 'The sample catalog is being prepared. Please try again later.'));
    exit;
}
header('Cache-Control: public, max-age=300');
readfile($path);
