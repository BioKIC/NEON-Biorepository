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
$reportsArr = $reports->getQuartleryReport($quarter);
$reportDate = $reports->getReportDate($quarter,'quarterly');
$headerArr = ['Statistic', 'Current','Change'];
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

		$general = [];
		$request = [];
		$sample  = [];

		foreach ($reportsArr as $row) {
			$type = array_shift($row); 

			switch ($type) {
				case 'general':
					$general[] = $row;
					break;
				case 'request':
					$request[] = $row;
					break;
				case 'sample':
					$sample[] = $row;
					break;
			}
		}

		if ($general) {
			echo '<h2>General Statistics</h2>';
			echo $utilities->htmlTable($general, $headerArr);
		}

		if ($request) {
			echo '<h2>Request Summary</h2>';
			echo $utilities->htmlTable($request, $headerArr);
		}


		if ($sample) {
			echo '<h2>Samples Received</h2>';
			echo $utilities->htmlTable($sample, $headerArr);
		}
	}
} 
else {
	echo '<h3>Please login to get access to this page.</h3>';
}
?>
	<form method="post" action="exportquarterlyreporthandler.php">
		<input type="hidden" name="quarter" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>">
		<button type="submit">Export Sample Use Report</button>
	</form>
	</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
  </body>
  <script src="../js/sortables.js"></script>
</html>