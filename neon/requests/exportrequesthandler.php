<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/RequestReport.php');

$reports = new RequestReport();
$reportsArr = $reports->getRequestsByStatus();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=requests_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['Status', 'Count']);

foreach ($reportsArr as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
