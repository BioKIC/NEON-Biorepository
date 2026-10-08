<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/PrepReports.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$reports = new PrepReports();
$reportsArr = $reports->getMamPrepsCntByPreparator();
$headerArr = ['Prepared By', 'Mammal study skins', 'Flat mammal skins', 'Mammal fluid preparations', 'Total prepared'];
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('CollAdmin',$USER_RIGHTS) || array_key_exists('CollEditor',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Preparations Reports</title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<link rel="stylesheet" href="../css/tables.css">
		<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div id="innertext">
			<?php
			if($isEditor){
				?>
        <?php
        echo '<h1>Preparations Reports</h1>';
        if(!empty($reportsArr)){
          $reportsTable = $utilities->htmlTable($reportsArr, $headerArr);
          echo $reportsTable;
          };
          ?>
				<?php
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