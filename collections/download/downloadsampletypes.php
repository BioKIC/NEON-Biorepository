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

$requestedIds = null;
if (array_key_exists('collids', $_GET)) {
    $value = $_GET['collids'];
    if (!is_string($value) || strlen($value) > 10000
        || !preg_match('/^(?:[1-9][0-9]{0,9}(?:,[1-9][0-9]{0,9})*)?$/D', $value)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(array('error' => 'Invalid sample type selection.'));
        exit;
    }
    // An explicit empty selection must never turn into a full-catalog download.
    $requestedIds = $value === '' ? array() : array_fill_keys(array_map('intval', explode(',', $value)), true);
}

try {
    $cachePath = $SERVER_ROOT . '/neon/cache/sample-catalog.json';
    if (!is_readable($cachePath)) throw new RuntimeException('Sample catalog cache is unavailable');
    $catalog = json_decode(file_get_contents($cachePath), true, 512, JSON_THROW_ON_ERROR);
    if (($catalog['version'] ?? null) !== 1 || !isset($catalog['items']) || !is_array($catalog['items'])) {
        throw new RuntimeException('Invalid sample catalog cache');
    }
    // Read the editable keywords on each download, independently of the catalog cache.
    $keywordsPath = $SERVER_ROOT . '/neon-react/biorepo_lib/keywords.json';
    $keywords = is_readable($keywordsPath) ? json_decode(file_get_contents($keywordsPath), true) : array();
    $summary = SampleTypeSummaryExport::read((new SampleDashboard())->getSummaryTable(), $catalog, is_array($keywords) ? $keywords : array());
    if ($requestedIds !== null) {
        $publicIds = array_fill_keys(array_column($catalog['items'], 'id'), true);
        $wanted = array_intersect_key($requestedIds, $publicIds);
        $summary['rows'] = array_values(array_filter($summary['rows'], function ($row) use ($wanted, $publicIds) {
            $id = (int)$row['collection_id'];
            if (!isset($publicIds[$id])) return false;
            if (isset($wanted[$id])) return true;
            // Identification collections are represented by their physical sample
            // entry and its existing Bulk Sample Identifications link in all formats.
            $url = html_entity_decode($row['bulk_sample_identifications'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $params);
            $identificationId = filter_var($params['collid'] ?? null, FILTER_VALIDATE_INT);
            return $identificationId && isset($wanted[$identificationId]);
        }));
    }
    if ($format === 'pdf') {
        $host = rtrim(($SERVER_HOST ?? '') ?: ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
        if (!preg_match('~^https?://~i', $host)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $scheme . $host;
        }
        $summary = SampleTypeSummaryExport::forPdf($catalog, $host . '/' . trim($CLIENT_ROOT, '/'), $summary);
        $content = SampleTypeSummaryExport::cachedPdf($summary, $SERVER_ROOT . '/neon/cache');
    } else {
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
$filename = $requestedIds === null ? 'NEON_Sample_Type_Summary' : 'NEON_Filtered_Sample_Type_Summary';
header('Content-Disposition: attachment; filename="' . $filename . date('Ymd') . '.' . $format . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
echo $content;
