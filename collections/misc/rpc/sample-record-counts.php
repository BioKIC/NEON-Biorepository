<?php
require_once dirname(__DIR__, 3) . '/config/symbini.php';
require_once $SERVER_ROOT . '/config/dbconnection.php';
require_once $SERVER_ROOT . '/neon/classes/SampleCatalogRecordCounts.php';
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(array('error' => 'Use POST for record counts.'));
    exit;
}
$connection = null;
try {
    $body = file_get_contents('php://input', false, null, 0, 131073);
    if (strlen($body) > 131072) throw new InvalidArgumentException('Record query is too large.');
    $input = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
    $queries = $input['queries'] ?? null;
    $path = $SERVER_ROOT . '/neon/cache/sample-catalog.json';
    if (!is_readable($path)) throw new RuntimeException('Sample catalog is unavailable');
    $catalog = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (($catalog['version'] ?? null) !== 1 || empty($catalog['items'])) throw new RuntimeException('Invalid sample catalog');
    $ids = array_column($catalog['items'], 'id');
    // Validate before using the cache. Only collections in the public catalog are eligible.
    SampleCatalogRecordCounts::query($queries, $ids);
    $key = 'sample-record-counts-v1:' . hash('sha256', $SERVER_ROOT . json_encode(array($queries, $ids, $catalog['generatedAt'] ?? filemtime($path))));
    $cacheEnabled = function_exists('apcu_enabled') && apcu_enabled();
    $found = false;
    $counts = $cacheEnabled ? apcu_fetch($key, $found) : null;
    if (!$found) {
        $connection = MySQLiConnectionFactory::getCon('readonly');
        if (!$connection) throw new RuntimeException('Database connection is unavailable');
        $counts = SampleCatalogRecordCounts::find($connection, $queries, $ids);
        if ($cacheEnabled) apcu_store($key, $counts, 300);
    }
    echo json_encode(array('counts' => (object)$counts), JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException | JsonException $error) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid record count query.'));
} catch (Throwable $error) {
    error_log('Sample catalog record counts: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(array('error' => 'Matching record counts are temporarily unavailable.'));
} finally {
    if ($connection) $connection->close();
}
