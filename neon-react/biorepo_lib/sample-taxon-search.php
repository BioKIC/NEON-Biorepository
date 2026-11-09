<?php
require_once dirname(__DIR__, 2) . '/config/symbini.php';
require_once $SERVER_ROOT . '/config/dbconnection.php';
require_once $SERVER_ROOT . '/neon/classes/SampleCatalogTaxonSearch.php';

// Do not hold the visitor's session lock while querying.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$connection = null;
try {
    $terms = SampleCatalogTaxonSearch::terms($_GET['terms'] ?? null);
    $path = $SERVER_ROOT . '/neon/cache/sample-catalog.json';
    if (!is_readable($path)) throw new RuntimeException('Sample catalog is unavailable');
    $catalog = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (($catalog['version'] ?? null) !== 1 || empty($catalog['items'])) {
        throw new RuntimeException('Invalid sample catalog');
    }
    // Only search collections already exposed in the public catalog. Never accept
    // collection IDs from the request or reveal unrelated repository collections.
    $ids = array_column($catalog['items'], 'id');
    $key = 'sample-taxon-v1:' . hash('sha256', $SERVER_ROOT . json_encode(array($terms, $ids, $catalog['generatedAt'] ?? filemtime($path))));
    $cacheEnabled = function_exists('apcu_enabled') && apcu_enabled();
    $found = false;
    $matches = $cacheEnabled ? apcu_fetch($key, $found) : null;
    if (!$found) {
        $connection = MySQLiConnectionFactory::getCon('readonly');
        if (!$connection) throw new RuntimeException('Database connection is unavailable');
        $matches = SampleCatalogTaxonSearch::find($connection, $ids, $terms);
        // Optional shared cache; the endpoint also works without APCu installed.
        if ($cacheEnabled) apcu_store($key, $matches, 300);
    }
    header('Cache-Control: private, max-age=300');
    echo json_encode(array('collectionIds' => $matches), JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    echo json_encode(array('error' => $error->getMessage()));
} catch (Throwable $error) {
    error_log('Sample catalog taxon search: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(array('error' => 'Scientific-name search is temporarily unavailable.'));
} finally {
    if ($connection) $connection->close();
}
