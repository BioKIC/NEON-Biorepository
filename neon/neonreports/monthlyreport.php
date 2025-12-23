<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$month = $_POST['month'] ?? $_GET['month'] ?? null;

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

if (!$month) {
    die('No report month selected.');
}

$reports = new NEONReports();
$reportsArr = $reports->getMonthlyReport($month);
$reportDate = $reports->getMonthlyReportDate($month);
$headerArr = ['Statistic', 'Current','Change'];
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

elseif(array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> NEON Monthly Report </title>
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
			<b>NEON Monthly Report</b>
		</div>
		<div id="innertext">
<?php
if ($isEditor) {
?>
	<h1>NEON Monthly Report: <?php echo htmlspecialchars($month); ?></h1>
 <?php

	if ($reportDate) {
		echo '<p><strong>Report generated: </strong> ' . htmlspecialchars($reportDate) . '</p>';
	}
	if (!empty($reportsArr)) {
		$reportsTable = $utilities->htmlTable($reportsArr, $headerArr);
		echo $reportsTable;
	}
} 
else {
	echo '<h3>Please login to get access to this page.</h3>';
}
?>

	<form method="post" action="exportmonthlyreporthandler.php">
		<input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>">
		<button type="submit">Export Report</button>
	</form>

		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
  </body>
  <script src="../js/sortables.js"></script>
</html>