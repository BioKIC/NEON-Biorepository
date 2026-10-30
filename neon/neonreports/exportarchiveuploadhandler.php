<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/ArchiveUpload.php');

$type = $_POST['type'] ?? $_GET['type'] ?? null;
$date = date('Y-m-d');

$reports = new ArchiveUpload();

$reportsArr = $reports->getArchiveExport($type);

if (empty($reportsArr)) {
    exit('No archive records found');
}

// Only mark unsubmitted records as submitted after exporting
if ($type == 0) {
    $reports->updateArchiveData();
    header('Content-Disposition: attachment; filename=neon_archive_unsubmitted_' . $date . '.csv');
}
elseif ($type == 1) {
    header('Content-Disposition: attachment; filename=neon_archive_submitted_' . $date . '.csv');
}

header('Content-Type: text/csv; charset=utf-8');

$output = fopen('php://output', 'w');

fputcsv($output, array_keys($reportsArr[0]));

foreach ($reportsArr as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
