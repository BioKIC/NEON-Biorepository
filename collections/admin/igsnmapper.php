<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSesar.php');
header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 3600);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/igsnmapper.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$igsnSeed = array_key_exists('igsnSeed',$_REQUEST)?$_REQUEST['igsnSeed']:'';
$processingCount = array_key_exists('processingCount',$_REQUEST)?$_REQUEST['processingCount']:10;
$action = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

//Variable sanitation
if(!is_numeric($collid)) $collid = 0;
if(preg_match('/[^A-Z0-9]+/', $igsnSeed)) $igsnSeed = '';
if($processingCount && !is_numeric($processingCount)) $processingCount = 10;

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
	$isEditor = 1;
}
$guidManager = new OccurrenceSesar();
$guidManager->setCollid($collid);
$guidManager->setCollArr();

if($igsnSeed) $guidManager->setIgsnSeed($igsnSeed);
else $igsnSeed = $guidManager->getIgsnSeed();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title>IGSN GUID Mapper</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript">
		function generateIgsnSeed(){
			var f = document.guidform;
			$("#igsnseed-div").show();
			$.ajax({
				method: "POST",
				data: { collid: f.collid.value },
				dataType: "text",
				url: "rpc/getigsnseed.php"
			})
			.done(function(responseStr) {
				f.igsnSeed.value = responseStr;
			});
		}

		function verifyGuidForm(f){
			if(f.igsnSeed.value == ""){
				alert("IGSN seed not generated");
				return false;
			}
			setTimeout(function(){
				//f.igsnSeed.value = "";
			}, 100);
			return true;
		}
	</script>
	<style type="text/css">
		fieldset{ margin:10px; padding:15px; }
		fieldset legend{ font-weight:bold; }
		.form-label{ font-weight: bold; }
		button{ margin:15px; }
	</style>
</head>
<body>
<?php
$displayLeftMenu = 'false';
include($SERVER_ROOT.'/includes/header.php');
?>
<div class='navpath'>
	<a href="../../index.php">Home</a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
	<a href="igsnmanagement.php?collid=<?php echo $collid; ?>">IGSN GUID Management</a> &gt;&gt;
	<b>IGSN Mapper</b>
</div>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	if($isEditor && $collid){
		if(!$guidManager->getProductionMode()){
			echo '<h2 style="color:orange">-- In Development Mode --</h2>';
		}
		echo '<h3>'.$guidManager->getCollectionName().'</h3>';
		if($statusStr){
			?>
			<fieldset style="margin:10px;">
				<legend>Error Panel</legend>
				<?php echo $statusStr; ?>
			</fieldset>
			<?php
		}
		if($action == 'populateGUIDs'){
			echo '<fieldset>';
			echo '<legend>Action Panel</legend>';
			echo '<ul>';
			$guidManager->batchProcessIdentifiers($processingCount);
			echo '<ul>';
			echo '</fieldset>';
		}
		?>
		<form id="guidform" name="guidform" action="igsnmapper.php" method="post" onsubmit="return verifyGuidForm(this)">
			<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
			<fieldset>
				<legend>IGSN Registration Control Panel</legend>
				<div style="margin:10px 0px">
					<p><b>Occurrences without GUIDs:</b> <?php echo $guidManager->getMissingGuidCount(); ?></p>
				</div>
				<div id="igsn-reg-div" style="margin-top:20px;">
					<div id="igsnseed-div" style="">
						<p>
							<span class="form-label">IGSN seed:</span>
							<input name="igsnSeed" type="text" value="<?php echo $igsnSeed; ?>" />
							<span style=""><a href="#" onclick="generateIgsnSeed();return false;"><img src="../../images/refresh.png" style="width:14px;vertical-align: middle;" /></a></span>
						</p>
					</div>
					<p>
						<span class="form-label">Number of identifiers to generate: </span>
						<input name="processingCount" type="text" value="10" /> (leave blank to process all specimens)
					</p>
					<p>
						<button name="formsubmit" type="submit" value="populateGUIDs">Populate Collection GUIDs</button>
					</p>
				</div>
			</fieldset>
		</form>
			<?php
	}
	else{
		echo '<h2>You are not authorized to access this page or collection identifier has not been set</h2>';
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>