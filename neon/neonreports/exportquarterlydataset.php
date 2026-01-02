<?php

error_reporting(E_ALL);
ini_set('display_errors', 0); 

require_once('../../config/symbini.php');
require_once($SERVER_ROOT.'/neon/classes/NEONReports.php');

$type    = $_POST['type']    ?? null;
$quarter = $_POST['quarter'] ?? null;

if (!$type || !$quarter) {
    http_response_code(400);
    exit;
}

if (!preg_match('/AY(\d{2})\s*Q([1-4])/', $quarter, $m)) {
    http_response_code(400);
    exit;
}

$ayYear = 2000 + (int)$m[1];
$q      = (int)$m[2];

switch ($q) {
    case 1:
        $start = ($ayYear - 1) . '-10-01';
        $end   = ($ayYear - 1) . '-12-31';
        break;
    case 2:
        $start = $ayYear . '-01-01';
        $end   = $ayYear . '-03-31';
        break;
    case 3:
        $start = $ayYear . '-04-01';
        $end   = $ayYear . '-06-30';
        break;
    case 4:
        $start = $ayYear . '-07-01';
        $end   = $ayYear . '-09-30';
        break;
}

$reports = new NEONReports();

switch ($type) {
    case 'data_edits':
        $rows = $reports->dataEdits($start, $end);
        $filename = "data_edits_{$quarter}.csv";
        break;

    case 'samples_generated':
        $rows = $reports->samplesGenerated($start, $end);
        $filename = "samples_generated_{$quarter}.csv";
        break;

    case 'datasets_generated':
        $rows = $reports->datasetsGenerated($start, $end);
        $filename = "datasets_generated_{$quarter}.csv";
        break;

    default:
        http_response_code(400);
        exit;
}

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

if (!empty($rows)) {
    fputcsv($output, array_keys($rows[0]));
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit; 
