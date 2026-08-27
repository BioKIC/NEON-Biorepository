<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/OccurrenceLoans.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$reports = new OccurrenceLoans();
$utilities = new Utilities();
$loansArr = $reports->getLoanOutAll();
$headerArr = ['loanId','requestor','dateSent','dateDue','dateClosed','totalSpecimens','specimensOut','assignee'];
$total = $reports->getOutSamplesCnt();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('CollAdmin',$USER_RIGHTS) || array_key_exists('CollEditor',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Loans Reports</title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<link rel="stylesheet" href="css/tables.css">
		<script src="<?= $CLIENT_ROOT ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?= $CLIENT_ROOT ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	</head>
	<body>
		<?php
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div id="innertext">
			<?php
			if($isEditor){
				echo '<h1>Loans Reports</h1>';
				echo '<p>Total number of samples in open loans: '.$total.'</p>';
				if(!empty($loansArr)){
					$loansTable = $utilities->htmlTable($loansArr, $headerArr);
					echo $loansTable;
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
	<script src="js/sortables.js"></script>
</html>