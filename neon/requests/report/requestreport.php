<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/RequestReport.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$reports = new RequestReport();
$reportsArr = $reports->getRequestsByStatus();
$headerArr = ['Status', 'Count'];
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Requests Report</title>
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
			<a href="../../index.php">Management Tools</a> &gt;&gt;
			<b>Requests Report</b>
		</div>
		<div id="innertext">
<?php
if ($isEditor) {
?>
  <h1>Requests Report</h1>

  <form method="post" action="exportrequesthandler.php">
    <button type="submit">Export Report</button>
  </form>


  <?php
  if (!empty($reportsArr)) {
    $reportsTable = $utilities->htmlTable($reportsArr, $headerArr);
    echo $reportsTable;
  }
} else {
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