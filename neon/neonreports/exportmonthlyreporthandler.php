<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');

$month = $_POST['month'] ?? $_GET['month'] ?? null;
$type  = $_POST['type']  ?? $_GET['type']  ?? null;

$reports = new NEONReports();
$reportsArr = $reports->getMonthlyReport($month);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=neon_monthly_report_' . $type . '_' . $month . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Statistic', 'Current', 'Change']);

foreach ($reportsArr as $row) {
    if ($row[0] === $type) {
        fputcsv($output, array_slice($row, 1));
    }
}

fclose($output);
exit;
?>
