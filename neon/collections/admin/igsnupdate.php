<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/OccurrenceSesar.php');
include_once($SERVER_ROOT.'/neon/classes/IgsnManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$processingCount = array_key_exists('processingCount',$_REQUEST)?$_REQUEST['processingCount']:10;
$parallelCount = array_key_exists('parallelCount',$_REQUEST)?$_REQUEST['parallelCount']:1;
$action = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

//Variable sanitation
if(!is_numeric($collid)) $collid = 0;

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
	$isEditor = 1;
}
$igsnManager = new IgsnManager();
$guidManager = new OccurrenceSesar();
$guidManager->setCollid($collid);
$guidManager->setCollArr();
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title>IGSN GUID Management</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript" src="../../../js/jquery.js"></script>
	<script type="text/javascript">
		function verifyGuidForm(f) {
			if (f.parallelCount) {
				const parallelValue = parseInt(f.parallelCount.value, 10);
				if (parallelValue > 100) {
					alert("The number of samples to update at a time (parallelCount) cannot exceed 100.");
					f.parallelCount.focus();
					return false;
				}
			}
			return true;
		}
	</script>
	<style type="text/css">
		fieldset{ margin:10px; padding:15px; }
		fieldset legend{ font-weight:bold; }
		.form-label{  }
		button{ margin:15px; }
	</style>
</head>
<body>
<?php
$displayLeftMenu = 'false';
include($SERVER_ROOT.'/includes/header.php');
?>
<div class='navpath'>
	<a href="../../../index.php">Home</a> &gt;&gt;
	<a href="../../index.php">NEON Biorepository Tools</a> &gt;&gt;
	<a href="../../igsncontrol.php">NEON IGSN Control Panel</a> &gt;&gt;
	<b>IGSN Update</b>
</div>
<div id="innertext">
	<?php
	if(!$guidManager->getProductionMode()){
		echo '<h2 style="color:orange">-- In Development Mode --</h2>';
	}
	if (!($collid)){
	?>
		<fieldset>
			<legend><b>Collections Needing Updates</b></legend>
			<div style="">
				<ul>
					<?php
					$taskList = $igsnManager->getIgsnUpdateReport();
					if($taskList){
						foreach($taskList as $collid => $collArr){
							echo '<li>'.$collArr['collname'].' ('.$collArr['collcode'].'): ';
							echo '<a href="igsnupdate.php?collid='.$collid.'" target="_blank">'.$collArr['cnt'].'</a></li>';
						}
					}
					else{
						echo '<div style="margin:20px"><b>All collection data is up to date on SESAR</b></div>';
					}
					?>
				</ul>
			</div>
		</fieldset>
<?php
	} else if ($isEditor && $collid) {
		echo '<h3>'.$guidManager->getCollectionName().'</h3>';
		if($statusStr){
			?>
			<fieldset style="margin:10px;">
				<legend>Error Panel</legend>
				<?php echo $statusStr; ?>
			</fieldset>
			<?php
		}
		if($action == 'updateSESAR'){
			echo '<fieldset>';
			echo '<legend>Action Panel</legend>';
			echo '<ul>';
			$guidManager->batchUpdateIdentifiers($processingCount, $parallelCount);
			echo '<ul>';
			echo '</fieldset>';
		}
		?>
		<form id="updateform" name="updateform" action="igsnupdate.php" method="post" onsubmit="return verifyGuidForm(this)">
			<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
			<fieldset>
				<legend>Update Control Panel</legend>
				<div style="margin:10px 0px">
					<p><b>Occurrences needing updates:</b> <?php echo $guidManager->getNeedsUpdateCount(); ?></p>
				</div>
				<div id="igsn-reg-div" style="margin-top:20px;">
					<p>
						<span class="form-label">Number of samples to update: </span>
						<input name="processingCount" type="number" value="10" /> (leave blank to process all)
					</p>
					<p>
						<span class="form-label">Number of samples to update at a time: </span>
						<input name="parallelCount" type="number" value="1" min="1" max="100" /> (up to 100 samples at a time)
					</p>
					<p>
						<button name="formsubmit" type="submit" value="updateSESAR">Update Sample Metadata</button>
					</p>
				</div>
			</fieldset>
		</form>
			<?php
	} else{
		echo '<h2>You are not authorized to access this page or collection identifier has not been set</h2>';
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>