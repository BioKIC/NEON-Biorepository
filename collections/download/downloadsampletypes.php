<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/SampleDashboard.php');

$summary = new SampleDashboard();
$summaryResult = $summary->getSummaryTable();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=NEON_Sample_Type_Summary' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');

$fields = $summaryResult->fetch_fields();

$headers = [];
foreach ($fields as $field) {
    $headers[] = $field->name;
}

fputcsv($output, array_slice($headers, 1));

while ($row = $summaryResult->fetch_assoc()) {
    fputcsv($output, array_slice($row, 1));
}

fclose($output);
exit;
?>