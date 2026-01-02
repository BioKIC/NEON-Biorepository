<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$quarter = $_POST['quarter'] ?? $_GET['quarter'] ?? null;


if (!$quarter) {
    die('No report quarter selected.');
}

$reports = new NEONReports();
$reportsArr = $reports->getQuarterlyReport($quarter);
$reportDate = $reports->getReportDate($quarter,'quarterly');
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

elseif(array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> NEON Quarterly Sample Use Report </title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<?php
		$activateJQuery = true;
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
    <link rel="stylesheet" href="../css/tables.css">
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="../../../index.php">Home</a> &gt;&gt;
			<a href="../index.php">Management Tools</a> &gt;&gt;
			<b>NEON Quarterly Sample Use Report</b>
		</div>
		<div id="innertext">
<?php
if ($isEditor) {
?>
	<h1>NEON Quarterly Sample Use Report: <?php echo htmlspecialchars($quarter); ?></h1>
 <?php

	if ($reportDate) {
		echo '<p><strong>Report generated: </strong> ' . htmlspecialchars($reportDate) . '</p>';
	}
	if (!empty($reportsArr)) {

		$tables = [];

		foreach ($reportsArr as $row) {
			$period    = $row['period'];
			$tabletype = $row['tabletype'];

			$tables[$period][$tabletype][] = $row;
		}

		foreach ($tables as $period => $tableTypes) {

			foreach ($tableTypes as $tableType => $rows) {

				foreach ($rows as &$r) {
					unset($r['pk'], $r['name'], $r['period'], $r['tabletype'], $r['date']);
				}
				unset($r);

				$rows =  $reports->removeNullColumns($rows);

				if (empty($rows)) continue;

				echo '<h2>' . htmlspecialchars($period) . ': ' . htmlspecialchars($tableType) . '</h2>';
				$headers = array_map(
					fn($h) => ucwords(str_replace('_', ' ', $h)),
					array_keys($rows[0])
				);

				echo $utilities->htmlTable(array_map('array_values', $rows),$headers);

		echo '
			<form method="post" action="exportquarterlyreporthandler.php" style="margin-bottom:20px;">
				<input type="hidden" name="quarter" value="' . htmlspecialchars($quarter, ENT_QUOTES) . '">
				<input type="hidden" name="period" value="' . htmlspecialchars($period, ENT_QUOTES) . '">
				<input type="hidden" name="tabletype" value="' . htmlspecialchars($tableType, ENT_QUOTES) . '">
				<button type="submit">Download CSV</button>
			</form>';

			}
		}
	}
?>
	<h2>Quarterly Data Exports</h2>

	<form method="post" action="exportquarterlydataset.php">
		<input type="hidden" name="type" value="data_edits">
		<input type="hidden" name="quarter" value="<?= htmlspecialchars($quarter, ENT_QUOTES) ?>">
		<button type="submit">Download Data Edits (CSV)</button>
	</form>

	<form method="post" action="exportquarterlydataset.php">
		<input type="hidden" name="type" value="samples_generated">
		<input type="hidden" name="quarter" value="<?= htmlspecialchars($quarter, ENT_QUOTES) ?>">
		<button type="submit">Download Samples Generated (CSV)</button>
	</form>

	<form method="post" action="exportquarterlydataset.php">
		<input type="hidden" name="type" value="datasets_generated">
		<input type="hidden" name="quarter" value="<?= htmlspecialchars($quarter, ENT_QUOTES) ?>">
		<button type="submit">Download Datasets Generated (CSV)</button>
	</form>
<?php

} 
else {
	echo '<h3>Please login to get access to this page.</h3>';
}
?>
	</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
  </body>
  <script src="../js/sortables.js"></script>
</html>