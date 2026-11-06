<?php
require_once dirname(__DIR__, 2) . '/config/symbini.php';
require_once $SERVER_ROOT . '/neon/classes/SampleDashboard.php';
require_once $SERVER_ROOT . '/neon/classes/SampleTypeSummaryExport.php';

// Long downloads must not hold the visitor's PHP session lock.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

$format = $_GET['format'] ?? 'csv';
if (!is_string($format) || !in_array($format, array('csv', 'json', 'pdf'), true)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Unsupported catalog download format.'));
    exit;
}

try {
    $cachePath = $SERVER_ROOT . '/neon/cache/sample-catalog.json';
    if (!is_readable($cachePath)) throw new RuntimeException('Sample catalog cache is unavailable');
    $catalog = json_decode(file_get_contents($cachePath), true, 512, JSON_THROW_ON_ERROR);
    if (($catalog['version'] ?? null) !== 1 || !isset($catalog['items']) || !is_array($catalog['items'])) {
        throw new RuntimeException('Invalid sample catalog cache');
    }
    if ($format === 'pdf') {
        $host = rtrim(($SERVER_HOST ?? '') ?: ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
        if (!preg_match('~^https?://~i', $host)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $scheme . $host;
        }
        $summary = SampleTypeSummaryExport::forPdf($catalog, $host . '/' . trim($CLIENT_ROOT, '/'));
        $content = SampleTypeSummaryExport::cachedPdf($summary, $SERVER_ROOT . '/neon/cache');
    } else {
        $summary = SampleTypeSummaryExport::read((new SampleDashboard())->getSummaryTable(), $catalog);
        if ($format === 'csv') $content = SampleTypeSummaryExport::csv($summary);
        else $content = json_encode($summary['rows'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
} catch (Throwable $error) {
    error_log('Sample type summary download failed: ' . $error->getMessage());
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(array('error' => 'The sample type summary could not be downloaded. Please check that neonsamplesummary is configured and the sample catalog cache has been refreshed.'));
    exit;
}

$types = array('csv' => 'text/csv; charset=utf-8', 'json' => 'application/json; charset=utf-8', 'pdf' => 'application/pdf');
header('Content-Type: ' . $types[$format]);
header('Content-Disposition: attachment; filename="NEON_Sample_Type_Summary' . date('Ymd') . '.' . $format . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
echo $content;
