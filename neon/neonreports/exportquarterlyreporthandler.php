<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');

$quarter = $_POST['quarter'] ?? $_GET['quarter'] ?? null;
$tabletype  = $_POST['tabletype']  ?? $_GET['tabletype']  ?? null;
$period  = $_POST['period']  ?? $_GET['period']  ?? null;


$reports = new NEONReports();
$reportsArr = $reports->getQuarterlyReport($quarter);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=neon_quarterly_report_' . $tabletype . '_' . $quarter . '_' . $period . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Status', 'Collection Name', 'Field', 'Requests', 'Researchers', 'Samples']);

foreach ($reportsArr as $row) {
    if ($row[0] === $tabletype && $row[0] === $quarter && $row[0] === $period) {
        fputcsv($output, array_slice($row, 1));
    }
}

# then drop from output any column that is all NULL

fclose($output);
exit;
?>
