<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');

$quarter   = $_POST['quarter']   ?? $_GET['quarter']   ?? null;
$tabletype = $_POST['tabletype'] ?? $_GET['tabletype'] ?? null;

if (!$quarter || !$tabletype) {
    die('Missing quarter or table type');
}

$reports = new NEONReports();
$reportsArr = $reports->getQuarterlyReport($quarter);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=neon_quarterly_report_' . $tabletype . '_' . $quarter . '.csv');

$output = fopen('php://output', 'w');
$periodData = [];

foreach ($reportsArr as $row) {

    if ($row['tabletype'] !== $tabletype) {
        continue;
    }

    $period = $row['period'];

    unset(
        $row['pk'],
        $row['name'],
        $row['period'],
        $row['tabletype'],
        $row['date']
    );

    $periodData[$period][] = $row;
}

foreach ($periodData as $period => $rows) {
    $periodData[$period] = $reports->removeNullColumns($rows);
}

$sampleRow = null;

foreach ($periodData as $rows) {
    if (!empty($rows)) {
        $sampleRow = $rows[0];
        break;
    }
}

if (!$sampleRow) {
    die('No data found');
}

$rowKeys = array_keys($sampleRow);
$rowLabelKey = $rowKeys[0];
$valueKeys   = array_slice($rowKeys, 1);


$headers = [ucwords(str_replace('_',' ',$rowLabelKey))];

foreach ($periodData as $period => $rows) {

    if (str_contains($quarter, 'Q1') && ($period === 'Award Year' || $period === 'Prior Award Year')) {
		continue;
	}

    if ($period == 'Quarter') {
        $title = $quarter;
    }
    elseif ($period == 'Award Year') {
        $title = substr($quarter, 0, 4);
    }
    elseif ($period == 'To Date') {
        $title = 'To Date';
    }
    else {
        $title = $period;
    }

    foreach ($valueKeys as $valKey) {
        if ($valKey == 'physicalSamples') $valKey = 'Physical Samples';
        $headers[] = $title . ': ' . ucwords($valKey);
    }
}

$allLabels = [];

foreach ($periodData as $rows) {
    foreach ($rows as $r) {
        $allLabels[$r[$rowLabelKey]] = true;
    }
}

$allLabels = array_keys($allLabels);

usort($allLabels, function($a, $b) {

    if ($a === 'Total Unique') return 1;
    if ($b === 'Total Unique') return -1;

	    return strcasecmp($a, $b);
	});

$finalRows = [];

foreach ($allLabels as $label) {

    $rowOut = [$label];

    foreach ($periodData as $period => $rows) {

        if (str_contains($quarter, 'Q1') && ($period === 'Award Year' || $period === 'Prior Award Year')) {
            continue;
        }

        $match = null;

        foreach ($rows as $r) {
            if ($r[$rowLabelKey] == $label) {
                $match = $r;
                break;
            }
        }

        foreach ($valueKeys as $valKey) {
            $rowOut[] = $match[$valKey] ?? 0;
        }
    }

    $finalRows[] = $rowOut;
}

fputcsv($output, $headers);

foreach ($finalRows as $r) {
    fputcsv($output, $r);
}

fclose($output);
exit;
?>

