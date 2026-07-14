<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/ArchiveUpload.php');

$type  = $_POST['type']  ?? $_GET['type']  ?? null;
$date = date('Y-m-d');

$reports = new ArchiveUpload();
$reportsArr = $reports->getArchiveExport($type);


if (empty($reportsArr)) {
    exit('No archive records found');
}

if ($type == 0) {
    $reports->updateArchiveData();
    header('Content-Disposition: attachment; filename=neon_archive_unsubmitted_' . $date . '.csv');
}
if ($type == 1) {
    header('Content-Disposition: attachment; filename=neon_archive_submitted_' . $date . '.csv');
}

header('Content-Type: text/csv; charset=utf-8');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'archiveLaboratoryName',
    'archiveStartDate',
    'sampleID',
    'sampleCode',
    'sampleFate',
    'sampleClass',
    'archiveMedium',
    'storageTemperature',
    'scientificName',
    'scientificNameAuthorship',
    'identificationQualifier',
    'sex',
    'reproductiveCondition',
    'lifeStage',
    'identifiedBy',
    'archiveGuid',
    'accessionNumber',
    'catalogueNumber',
    'externalURLs',
    'collectionCode',
    'remarks'
]);

foreach ($reportsArr as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
