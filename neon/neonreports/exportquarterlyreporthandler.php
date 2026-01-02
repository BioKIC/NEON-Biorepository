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
$rows = [];

foreach ($reportsArr as $row) {
    if (
        $row['tabletype'] === $tabletype &&
        $row['name'] === $quarter &&
        $row['period'] === $period
    ) {
        unset($row['pk'], $row['name'], $row['period'], $row['tabletype'], $row['date']);
        $rows[] = $row;
    }
}

$rows = $reports->removeNullColumns($rows);

if (!empty($rows)) {
    fputcsv($output, array_keys($rows[0]));
    foreach ($rows as $r) {
        fputcsv($output, array_values($r));
    }
}

fclose($output);
exit;
?>
