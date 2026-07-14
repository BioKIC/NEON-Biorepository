<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/ArchiveUpload.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);


$reports = new ArchiveUpload();
$reportsArr = $reports->getArchiveData();
$utilities = new Utilities();
$isEditor = false;
if($IS_ADMIN) $isEditor = true;

elseif(array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> NEON Archive Upload </title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<?php
		$activateJQuery = true;
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
    <link rel="stylesheet" href="../css/tables.css">
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div id="innertext">
<?php
if ($isEditor) {
?>
	<h1>NEON Archive Upload Data?></h1>
 <?php


	if (!empty($reportsArr)) {
		
		echo '<h4><a href=https://data.neonscience.org/web/external-lab-ingest>NEON External Lab Ingest Page</a></h4>';
		
        $unsubmitted = [];
		$submitted = [];

		foreach ($reportsArr as $row) {
			$status = array_shift($row); 

			switch ($status) {
                case 0:
					$unsubmitted[] = $row;
					break;
				case 1:
					$submitted[] = $row;
					break;
			}
		}


		if ($unsubmitted) {
			echo '<h2>Unsubmitted Samples</h2>';
			$headerArr = ['Status', 'Current','Change'];
			echo $utilities->htmlTable($unsubmitted, $headerArr);
		}

		?>
		<form method="post" action="exportarchiveuploadhandler.php">
			<input type="hidden" name="type" value="unsubmitted">
			<button type="submit">Export New Archive Upload Data</button>
		</form>
		<?php

        if ($submitted) {
			echo '<h2>Submitted Samples</h2>';
			$headerArr = ['Statistic', 'Current','Change'];
			echo $utilities->htmlTable($submitted, $headerArr);
		}

		?>
		<form method="post" action="exportarchiveuploadhandler.php">
			<input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="submitted">
			<button type="submit">Export Prior Archive Upload Data</button>
		</form>
		<?php

	}
?>

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