<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/ShipmentManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/shipment/manifestsearch.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$shipmentPK = array_key_exists("shipmentPK",$_REQUEST)?$_REQUEST["shipmentPK"]:"";

$shipManager = new ShipmentManager();
$shipManager->setShipmentPK($shipmentPK);

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('CollAdmin',$USER_RIGHTS) || array_key_exists('CollEditor',$USER_RIGHTS)) $isEditor = true;

$status = "";
if($isEditor){
	if($action == 'exportManifests'){
		$shipManager->exportShipmentList();
	}
	elseif($action == 'exportSamples'){
		$shipManager->exportSampleList();
	}
	elseif($action == 'exportOccurrences'){
		$shipManager->exportOccurrenceList();
	}
}
?>

<script src="../js/multiselect-dropdown.js" ></script>

<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Manifest Viewer</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function fullResetForm(f){
			f.shipmentID.value = "";
			f.sampleID.value = "";
			f.sampleCode.value = "";
			f.domainID.value = "";
			f.namedLocation.value = "";
			f.sampleClass.value = "";
			f.taxonID.value = "";
			f.trackingNumber.value = "";
			f.dynamicProperties.value = "";
			f.occid.value = "";
			f.dateShippedStart.value = "";
			f.dateShippedEnd.value = "";
			f.dateCheckinStart.value = "";
			f.dateCheckinEnd.value = "";
			$('select[name="checkinUid[]"]').val([]);
			$('select[name="checkinsampleUid[]"]').val([]);
			f.sessionData.value = "";
			$('select[name="importedUid[]"]').val([]);
			f.sampleCondition.value = "";
			var radioList = document.getElementsByName('manifestStatus[]');
			for(x = 0; x < radioList.length; x++){
				radioList[x].checked = false;
			}
			f.submit();
		}

		function copyUrl($urlFrag){
			var $temp = $("<input>");
			$("body").append($temp);
			var activeLink = "<?php echo $_SERVER['HTTP_HOST'].$CLIENT_ROOT; ?>/neon/shipment/manifestsearch.php?shipmentID="+$urlFrag;
			$temp.val(activeLink).select();
			document.execCommand("copy");
			$temp.remove();
			$("#copiedDiv").show().delay(2000).fadeOut();
		}
	</script>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<style type="text/css">
		#innertext{ max-width: 1500px; }
		.fieldGroupDiv{ clear:both; margin-top:2px; height: 25px; }
		.fieldDiv{ float:left; margin-left: 25px}
		fieldset {
			border: 1px solid #c0c0c0;
			margin: 0 2px;
			padding: 0.35em 0.625em 0.75em;
			border-radius: 10px;
		}
		legend {width: auto;}
		button,
		button.ui-button.ui-widget.ui-corner-all,
		input[type='submit' i] {
			text-transform: uppercase;
			font-size: 0.5rem;
		}
		
		.form-control, .form-select, textarea, input, select { border-radius: .25rem !important; margin: 5px; font-size: .8rem;}
		form button {
		  margin: 5px !important;
		}
	</style>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../../index.php">Home</a> &gt;&gt;
	<a href="../index.php">NEON Biorepository Tools</a> &gt;&gt;
	<a href="manifestsearch.php"><b>Manifest Search</b></a>
</div>
<div id="innertext">
	<?php
	if($isEditor){
		$shipmentDetails = $shipManager->getShipmentList();
		$searchArgumentArr = $shipManager->getSearchArr();
		?>
		<fieldset style="position: relative">
			<legend style="margin-bottom: -15px"><b>Manifest Search</b></legend>
			<?php
			$searchArgument = $shipManager->getSearchArgumentStr();
			if($searchArgument){
			?>
				<div style="position:absolute;right:10px;">
					<div id="copiedDiv" style="float:left;display:none;margin-right:15px;font-size:80%">URL copied to clipboard</div>
					<a href="#" onclick="copyUrl('<?php echo $searchArgument; ?>')" title="Copy URL to Clipboard">
						<img src="../../images/link2.png" style="width:15px;" />
					</a>
				</div>
				<?php
			}
			?>
			
			<form action="manifestsearch.php" method="post" class="w-100">
			  <!-- Action bar -->
			  <div class="container-fluid py-2">
				<div class="row g-2 align-items-center">
				  <div class="col-12 col-md">
					<div class="d-flex flex-wrap gap-2">
					  <button name="action" type="submit" value="listManifests" class="btn btn-primary">Search</button>
					  <button type="button" class="btn btn-secondary" onclick="fullResetForm(this.form)">Clear</button>
					</div>
				  </div>
				  <div class="col-12 col-md-auto">
					<div class="d-flex flex-wrap gap-2 justify-content-md-end">
					  <button name="action" type="submit" value="exportManifests" class="btn btn-outline-primary">Download Manifests</button>
					  <button name="action" type="submit" value="exportSamples" class="btn btn-outline-primary">Download Samples</button>
					  <button name="action" type="submit" value="exportOccurrences" class="btn btn-outline-primary">Download Occurrences</button>
					</div>
				  </div>
				</div>
			  </div>
			
			  <!-- Identifiers -->
			  <fieldset class="container-fluid mt-3">
				<legend class="fs-6">Identifiers</legend>
				<div class="row g-3">
				  <!-- Column 1 & 2 combined -->
				  <div class="col-12 col-lg-4">
					<div class="row g-2">
					  <!-- Col 1 -->
					  <div class="col-12 col-sm-6">
						<input name="shipmentID" class="form-control" type="text" placeholder="Shipment ID"
						  value="<?php echo (isset($searchArgumentArr['shipmentID'])?$searchArgumentArr['shipmentID']:''); ?>">
					  </div>
					  <div class="col-12 col-sm-6">
						<input name="taxonID" class="form-control" type="text" placeholder="Taxon ID"
						  value="<?php echo (isset($searchArgumentArr['taxonID'])?$searchArgumentArr['taxonID']:''); ?>">
					  </div>
					  <!-- Col 2 -->
					  <div class="col-12 col-sm-6">
						<input name="domainID" class="form-control" type="text" placeholder="Domain ID"
						  value="<?php echo (isset($searchArgumentArr['domainID'])?$searchArgumentArr['domainID']:''); ?>">
					  </div>
					  <div class="col-12 col-sm-6">
						<input name="namedLocation" class="form-control" type="text" placeholder="Site ID"
						  value="<?php echo (isset($searchArgumentArr['namedLocation'])?$searchArgumentArr['namedLocation']:''); ?>">
					  </div>
					  <!-- Row 3 & 4 -->
					  <div class="col-12">
						<input name="sampleClass" class="form-control" type="text" placeholder="Sample Class"
						  value="<?php echo (isset($searchArgumentArr['sampleClass'])?$searchArgumentArr['sampleClass']:''); ?>">
					  </div>
					  <div class="col-12">
						<input name="trackingNumber" class="form-control" type="text" placeholder="Tracking Number"
						  value="<?php echo (isset($searchArgumentArr['trackingNumber'])?$searchArgumentArr['trackingNumber']:''); ?>">
					  </div>
					</div>
				  </div>
			
				  <!-- Column 3 - Textarea -->
				  <div class="col-12 col-sm-6 col-lg-2">
					<textarea name="occid" class="form-control" rows="8" placeholder="occid"><?php echo isset($searchArgumentArr['occid']) ? $searchArgumentArr['occid'] : ''; ?></textarea>
				  </div>
			
				  <!-- Column 4 - Textarea -->
				  <div class="col-12 col-sm-6 col-lg-3">
					<textarea name="sampleID" class="form-control" rows="8" placeholder="Sample ID"><?php echo isset($searchArgumentArr['sampleID']) ? $searchArgumentArr['sampleID'] : ''; ?></textarea>
				  </div>
			
				  <!-- Column 5 - Textarea -->
				  <div class="col-12 col-lg-3">
					<textarea name="sampleCode" class="form-control" rows="8" placeholder="Sample Code"><?php echo isset($searchArgumentArr['sampleCode']) ? $searchArgumentArr['sampleCode'] : ''; ?></textarea>
				  </div>
				</div>
			  </fieldset>
			
			  <!-- Dates -->
			  <fieldset class="container-fluid mt-4">
				<legend class="fs-6">Dates</legend>
			
				<div class="row g-3 align-items-center">
				  <div class="col-12 col-sm-3 col-md-2">
					<label class="col-form-label">Date Shipped:</label>
				  </div>
				  <div class="col-12 col-sm-9 col-md-10">
					<div class="row g-2 align-items-center">
					  <div class="col-12 col-sm-5 col-md-3">
						<input name="dateShippedStart" class="form-control" type="date"
						  value="<?php echo (isset($searchArgumentArr['dateShippedStart']) ? $searchArgumentArr['dateShippedStart'] : ''); ?>">
					  </div>
					  <div class="col-auto">–</div>
					  <div class="col-12 col-sm-5 col-md-3">
						<input name="dateShippedEnd" class="form-control" type="date"
						  value="<?php echo (isset($searchArgumentArr['dateShippedEnd']) ? $searchArgumentArr['dateShippedEnd'] : ''); ?>">
					  </div>
					</div>
				  </div>
				</div>
			
				<div class="row g-3 align-items-center mt-1">
				  <div class="col-12 col-sm-3 col-md-2">
					<label class="col-form-label">Sample Check-in Date:</label>
				  </div>
				  <div class="col-12 col-sm-9 col-md-10">
					<div class="row g-2 align-items-center">
					  <div class="col-12 col-sm-5 col-md-3">
						<input name="dateCheckinStart" class="form-control" type="date"
						  value="<?php echo (isset($searchArgumentArr['dateCheckinStart']) ? $searchArgumentArr['dateCheckinStart'] : ''); ?>">
					  </div>
					  <div class="col-auto">–</div>
					  <div class="col-12 col-sm-5 col-md-3">
						<input name="dateCheckinEnd" class="form-control" type="date"
						  value="<?php echo (isset($searchArgumentArr['dateCheckinEnd']) ? $searchArgumentArr['dateCheckinEnd'] : ''); ?>">
					  </div>
					</div>
				  </div>
				</div>
			  </fieldset>
			
			  <!-- Staff Activity -->
			  <fieldset class="container-fluid mt-4">
				<legend class="fs-6">Staff Activity</legend>
			
				<div class="row g-3 align-items-start">
				  <div class="col-12 col-md-3">
					<label class="col-form-label">Imported/Modified by:</label>
				  </div>
				  <div class="col-12 col-md-5" style="padding-top: calc(.375rem + 1px);">
					<select name="importedUid[]" class="form-select" multiple multiselect-search="true" size="6">
					  <?php
						$userImportArr = $shipManager->getImportUserArr();
						foreach($userImportArr as $uid => $userName){
						  echo '<option value="'.$uid.'" '.(isset($searchArgumentArr['importedUid'])&& in_array($uid, $searchArgumentArr['importedUid'])?'SELECTED':'').'>'.$userName.'</option>';
						}
					  ?>
					</select>
				  </div>
				</div>
			
				<div class="row g-3 align-items-start mt-1">
				  <div class="col-12 col-md-3">
					<label class="col-form-label">Shipment Checked In by:</label>
				  </div>
				  <div class="col-12 col-md-5" style="padding-top: calc(.375rem + 1px);">
					<select name="checkinUid[]" multiple class="form-select" multiselect-search="true" size="6">
					  <?php
						$usercheckinArr = $shipManager->getCheckinUserArr();
						foreach($usercheckinArr as $uid => $userName){
						  echo '<option value="'.$uid.'" '.(isset($searchArgumentArr['checkinUid']) && in_array($uid, $searchArgumentArr['checkinUid'])?'SELECTED':'').'>'.$userName.'</option>';
						}
					  ?>
					</select>
				  </div>
				</div>
			
				<div class="row g-3 align-items-start mt-1">
				  <div class="col-12 col-md-3">
					<label class="col-form-label">Samples Checked In by:</label>
				  </div>
				  <div class="col-12 col-md-5" style="padding-top: calc(.375rem + 1px);">
					<select name="checkinsampleUid[]" multiple class="form-select" multiselect-search="true" size="6">
					  <?php
						$usercheckinArr = $shipManager->getCheckinUserArr();
						foreach($usercheckinArr as $uid => $userName){
						  echo '<option value="'.$uid.'" '.(isset($searchArgumentArr['checkinsampleUid']) && in_array($uid, $searchArgumentArr['checkinsampleUid']) ? 'SELECTED' : '').'>'.$userName.'</option>';
						}
					  ?>
					</select>
				  </div>
				</div>
			  </fieldset>
			
			  <!-- Sessioning -->
			  <fieldset class="container-fluid mt-4">
				<legend class="fs-6">Sessioning</legend>
				<div class="row g-3 align-items-center">
				  <div class="col-12 col-md-2">
					<label class="col-form-label">Session:</label>
				  </div>
				  <div class="col-12 col-md-3">
					<select name="sessionData" class="form-select">
					  <option value="">All Records</option>
					  <option value="">------------------------</option>
					  <?php
						$sessionDataArr = $shipManager->getSessionDataArr();
						foreach($sessionDataArr as $key => $sessionName){
						  echo '<option value="'.htmlspecialchars($key).'" '.(isset($searchArgumentArr['sessionData'])&&$key==$searchArgumentArr['sessionData']?'SELECTED':'').'>'.$sessionName.'</option>';
						}
					  ?>
					</select>
				  </div>
				</div>
			  </fieldset>
			
			  <!-- Sample Properties -->
			  <fieldset class="container-fluid mt-4">
				<legend class="fs-6">Sample Properties</legend>
			
				<div class="row g-3 align-items-center">
				  <div class="col-12 col-md-3">
					<label class="col-form-label">Sample Condition:</label>
				  </div>
				  <div class="col-12 col-md-3">
					<select name="sampleCondition" class="form-select">
					  <option value="">All Records</option>
					  <option value="">------------------------</option>
					  <?php
						if($condArr = $shipManager->getConditionAppliedArr()){
						  foreach($condArr as $condKey => $condValue){
							echo '<option value="'.$condKey.'" '.(isset($searchArgumentArr['sampleCondition'])&&$condKey==$searchArgumentArr['sampleCondition']?'SELECTED':'').'>'.$condValue.'</option>';
						  }
						} else {
						  echo '<option value="">Sample Conditions have not been set</option>';
						}
					  ?>
					</select>
				  </div>
				</div>
			
				<div class="row g-3 align-items-center mt-1">
				  <div class="col-12 col-md-3">
					<label class="col-form-label">Dynamic Properties:</label>
				  </div>
				  <div class="col-12 col-md-3">
					<input name="dynamicProperties" class="form-control" type="text"
					  value="<?php echo (isset($searchArgumentArr['dynamicProperties'])?$searchArgumentArr['dynamicProperties']:''); ?>">
				  </div>
				</div>
			  </fieldset>
			
			  <!-- Status Filters -->
			  <?php $manifestStatus = isset($searchArgumentArr['manifestStatus'])?implode(',', $searchArgumentArr['manifestStatus']):''; ?>
			  <fieldset class="container-fluid mt-4">
				<legend class="fs-6">Status Filters</legend>
			
				<div class="row g-3">
				  <div class="col-12 col-md-4">
					<div class="form-check">
					  <input name="manifestStatus[]" class="form-check-input" type="checkbox" value="sampleNotCheck" id="st1"
						<?php echo ((strpos($manifestStatus, 'sampleNotCheck') !== false) ? 'checked' : ''); ?>>
					  <label class="form-check-label" for="st1">Samples Not Checked In</label>
					</div>
				  </div>
				  <div class="col-12 col-md-4">
					<div class="form-check">
					  <input name="manifestStatus[]" class="form-check-input" type="checkbox" value="sampleCheck" id="st2"
						<?php echo ((strpos($manifestStatus, 'sampleCheck') !== false) ? 'checked' : ''); ?>>
					  <label class="form-check-label" for="st2">Samples Checked In</label>
					</div>
				  </div>
				  <div class="col-12 col-md-4">
					<div class="form-check">
					  <input name="manifestStatus[]" class="form-check-input" type="checkbox" value="allSamplesChecked" id="st3"
						<?php echo ((strpos($manifestStatus, 'allSamplesChecked') !== false) ? 'checked' : ''); ?>>
					  <label class="form-check-label" for="st3">All Samples Checked In</label>
					</div>
				  </div>
			
				  <div class="col-12 col-md-4">
					<div class="form-check">
					  <input name="manifestStatus[]" class="form-check-input" type="checkbox" value="notAcceptedSamples" id="st4"
						<?php echo ((strpos($manifestStatus, 'notAcceptedSamples') !== false) ? 'checked' : ''); ?>>
					  <label class="form-check-label" for="st4">Samples Not Accepted</label>
					</div>
				  </div>
				  <div class="col-12 col-md-4">
					<div class="form-check">
					  <input name="manifestStatus[]" class="form-check-input" type="checkbox" value="notReceivedSamples" id="st5"
						<?php echo ((strpos($manifestStatus, 'notReceivedSamples') !== false) ? 'checked' : ''); ?>>
					  <label class="form-check-label" for="st5">Samples Not Received</label>
					</div>
				  </div>
				  <div class="col-12 col-md-4">
					<div class="form-check">
					  <input name="manifestStatus[]" class="form-check-input" type="checkbox" value="occurNotHarvested" id="st6"
						<?php echo ((strpos($manifestStatus, 'occurNotHarvested') !== false) ? 'checked' : ''); ?>>
					  <label class="form-check-label" for="st6">Occurrences Not Harvested</label>
					</div>
				  </div>
			
				  <div class="col-12 col-md-4">
					<div class="form-check">
					  <input name="manifestStatus[]" class="form-check-input" type="checkbox" value="shipNotCheck" id="st7"
						<?php echo ((strpos($manifestStatus, 'shipNotCheck') !== false) ? 'checked' : ''); ?>>
					  <label class="form-check-label" for="st7">Shipments Not Checked In</label>
					</div>
				  </div>
				  <div class="col-12 col-md-4">
					<div class="form-check">
					  <input name="manifestStatus[]" class="form-check-input" type="checkbox" value="shipCheck" id="st8"
						<?php echo ((strpos($manifestStatus, 'shipCheck') !== false) ? 'checked' : ''); ?>>
					  <label class="form-check-label" for="st8">Shipments Checked In</label>
					</div>
				  </div>
				  <div class="col-12 col-md-4">
					<div class="form-check">
					  <input name="manifestStatus[]" class="form-check-input" type="checkbox" value="receiptNotSubmitted" id="st9"
						<?php echo ((strpos($manifestStatus, 'receiptNotSubmitted') !== false) ? 'checked' : ''); ?>>
					  <label class="form-check-label" for="st9">Receipt Not Submitted</label>
					</div>
				  </div>
				</div>
			  </fieldset>
			
			  <!-- Bottom action bar (duplicate of top for convenience) -->
			  <div class="container-fluid py-3">
				<div class="row g-2 align-items-center">
				  <div class="col-12 col-md">
					<div class="d-flex flex-wrap gap-2">
					  <button name="action" type="submit" value="listManifests" class="btn btn-primary">Search</button>
					  <button type="button" class="btn btn-secondary" onclick="fullResetForm(this.form)">Clear</button>
					</div>
				  </div>
				  <div class="col-12 col-md-auto">
					<div class="d-flex flex-wrap gap-2 justify-content-md-end">
					  <button name="action" type="submit" value="exportManifests" class="btn btn-outline-primary">Download Manifests</button>
					  <button name="action" type="submit" value="exportSamples" class="btn btn-outline-primary">Download Samples</button>
					  <button name="action" type="submit" value="exportOccurrences" class="btn btn-outline-primary">Download Occurrences</button>
					</div>
				  </div>
				</div>
			  </div>
			</form>

		</fieldset>
		<fieldset style="margin-top:30px;padding:15px">
			<legend><b>Shipment Listing</b></legend>
			<ul>
				<?php
				if($shipmentDetails){
					foreach($shipmentDetails as $shipPK => $shipArr){
						echo '<li><a href="manifestviewer.php?shipmentPK='.$shipPK.'" target="_blank">'.$shipArr['id'].'</a> ('.$shipArr['ts'].')</li>';
					}
				}
				else{
					echo '<div>No manifest matching search criteria</div>';
				}
				?>
			</ul>
		</fieldset>
		<?php
	}
	else{
		?>
		<div style='font-weight:bold;margin:30px;'>
			You do not have permissions to view manifests
		</div>
		<?php
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>