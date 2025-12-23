<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');

$month = $_POST['month'] ?? $_GET['month'] ?? null;

$reports = new NEONReports();
$reportsArr = $reports->getMonthlyReport($month);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=neon_monthly_report_' . $month . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['Statistic','Current','Change']);

foreach ($reportsArr as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
