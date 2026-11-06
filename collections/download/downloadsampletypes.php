<?php
require_once dirname(__DIR__, 2) . '/config/symbini.php';
require_once $SERVER_ROOT . '/neon/classes/SampleDashboard.php';
require_once $SERVER_ROOT . '/neon/classes/SampleTypeSummaryExport.php';

$format = $_GET['format'] ?? 'csv';
if (!is_string($format) || !in_array($format, array('csv', 'json', 'pdf'), true)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Unsupported catalog download format.'));
    exit;
}

try {
    $summary = SampleTypeSummaryExport::read((new SampleDashboard())->getSummaryTable());
    if ($format === 'csv') $content = SampleTypeSummaryExport::csv($summary);
    elseif ($format === 'json') $content = json_encode($summary['rows'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    else $content = SampleTypeSummaryExport::pdf($summary);
} catch (Throwable $error) {
    error_log('Sample type summary download failed: ' . $error->getMessage());
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(array('error' => 'The sample type summary could not be downloaded. Please check that neonsamplesummary is configured and try again.'));
    exit;
}

$types = array('csv' => 'text/csv; charset=utf-8', 'json' => 'application/json; charset=utf-8', 'pdf' => 'application/pdf');
header('Content-Type: ' . $types[$format]);
header('Content-Disposition: attachment; filename="NEON_Sample_Type_Summary' . date('Ymd') . '.' . $format . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
echo $content;
