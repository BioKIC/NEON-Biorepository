<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/requests/InquirySampleLoadManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/requests/sampleloader.php');


$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$ulFileName = array_key_exists("ulfilename",$_REQUEST)?$_REQUEST["ulfilename"]:"";
$request_id = array_key_exists('id', $_REQUEST) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT) : '';


$isEditor = false;
if($IS_ADMIN) $isEditor = true;

$inquiryLoadManager = new inquirySampleLoadManager();

$fieldMap = array();
if($isEditor){
	if($ulFileName){
		$inquiryLoadManager->setUploadFileName($ulFileName);
	}
	if(array_key_exists("sf",$_POST)){
		//Grab field mapping, if mapping form was submitted
		$targetFields = $_REQUEST["tf"];
 		$sourceFields = $_REQUEST["sf"];
		for($x = 0;$x<count($targetFields);$x++){
			if($targetFields[$x] && $sourceFields[$x]) $fieldMap[$sourceFields[$x]] = $targetFields[$x];
		}
		$inquiryLoadManager->setFieldMap($fieldMap);
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Sample Loader </title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function verifyUploadForm(f){
			var status = true;
			var fileName = f.uploadfile.value;
			if(fileName == ""){
				alert("Select a sample file to upload");
				return false;
			}
			else{
				var ext = fileName.split('.').pop().toLowerCase();
				if(ext == "xlsx" || ext == "xls"){
					alert("Unable to import Excel files (.xlsx, .xls). Save file in the CSV format.");
					return false;
				}
				else if(ext != "csv"){
					status = confirm("Is the import file in the CSV format? If not, select cancel, save file in the CSV format, and reimport.");
				}
			}
			return status;
		}

		function verifyMappingForm(f){
			var sfArr = [];
			var tfArr = [];
			for(var i=0;i<f.length;i++){
				var obj = f.elements[i];
				if(obj.name == "sf[]"){
					if(obj.value.trim() != "" && sfArr.indexOf(obj.value) > -1){
						alert("ERROR: Source field names must be unique (duplicate field: "+obj.value+")");
						return false;
					}
					sfArr[sfArr.length] = obj.value;
				}
				else if(obj.name == "tf[]" && obj.value != "" && obj.value != "unmapped"){
					if(tfArr.indexOf(obj.value) > -1){
						alert('ERROR: Can\'t map to the same target field "'+obj.value+'" more than once');
						return false;
					}
					tfArr[tfArr.length] = obj.value;
				}
			}
		}
	</script>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../../index.php">Home</a> &gt;&gt;
	<a href="../../neon/index.php">Management Tools</a> &gt;&gt;
	<a href="../../neon/requests/inquiries.php">Inquiry List</a> &gt;&gt;
	<a href="inquiryform.php?id=<?php echo $request_id; ?>">Inquiry Record</a> &gt;&gt;
    <a href="samplelist.php?id=<?php echo $request_id; ?>">Sample List</a> &gt;&gt;
	<b>Sample Loader</b>
</div>
<?php
if($isEditor){
	?>
	<div id="innertext">
		<h1>Sample Loader</h1>
		<div style="margin:30px;">
			<?php
			if($action == 'Map Input File'){
				if(!$ulFileName) $inquiryLoadManager->uploadManifestFile();
				$analyzeStatus = $inquiryLoadManager->analyzeUpload();
				$errCode = 1;
				if(!$analyzeStatus){
					$errStr = $inquiryLoadManager->getErrorStr();
					if(strpos($errStr,'shipment already in system')){
						echo $errStr;
						?>
						<div>Are you sure you want to append the samples to existing inquiry?</div>
						<div style="margin-left:15px">If so, <a href="#" onclick="$('#mappingFormDiv').show();return false">click here to continue</a></div>
						<div style="margin-left:15px">If not, modify the requestIDs for each record and <a href="sampleloader.php">reload the manifest</a></div>
						<?php
						$errCode = 2;
					}
					else{
						echo '<div style="font-weight:bold">ERROR analyzing import file: '.$errStr.'</div>';
						$errCode = 0;
					}
				}
				if($errCode){
					?>
					<div id="mappingFormDiv" style="<?php if($errCode==2) echo 'display:none'; ?>">
						<form name="mappingform" action="sampleloader.php" method="post" onsubmit="return verifyMappingForm(this)">
							<fieldset style="width:90%;">
								<legend style="font-weight:bold;font-size:120%;">Sample Upload Form for Inquiry # <?php echo $request_id?></legend>
								<div style="margin:10px;">
								</div>
								<table class="styledtable" style="width:350px;">
									<tr>
										<th>
											Source Field
										</th>
										<th>
											Target Field
										</th>
									</tr>
									<?php
									$sourceArr = $inquiryLoadManager->getSourceArr();
									$targetArr = $inquiryLoadManager->getTargetArr();
									$translationMap = array('request_id'=>'request_id','occid'=>'occid','status'=>'status','use_type'=>'use_type','substance_provided'=>'substance_provided',
										'available'=>'available','notes'=>'notes','shipment_id'=>'shipment_id');
									foreach($sourceArr as $sourceField){
										?>
										<tr>
											<td style='padding:2px;'>
												<?php echo $sourceField; ?>
												<input type="hidden" name="sf[]" value="<?php echo $sourceField; ?>" />
											</td>
											<td>
												<?php
												$translatedSourceField = strtolower($sourceField);
												if(array_key_exists($translatedSourceField, $translationMap)) $translatedSourceField = $translationMap[$translatedSourceField];
												$bgColor = 'yellow';
												if($inquiryLoadManager->array_key_iexists($translatedSourceField,$fieldMap)) $bgColor = 'white';
												elseif($inquiryLoadManager->in_iarray($translatedSourceField, $targetArr)) $bgColor = 'white';
												?>
												<select name="tf[]" style="background:<?php echo $bgColor; ?>">
													<option value="">Field Unmapped</option>
													<option value="">-------------------------</option>
													<?php
													$matchTerm = '';
													if($inquiryLoadManager->array_key_iexists($translatedSourceField,$fieldMap)) $matchTerm = strtolower($fieldMap[$translatedSourceField]);
													else $matchTerm = $translatedSourceField;
													foreach($targetArr as $targetField){
														echo '<option '.($matchTerm==strtolower($targetField)?'SELECTED':'').'>'.$targetField.'</option>';
													}
													?>
												</select>
											</td>
										</tr>
										<?php
									}
									?>
								</table>
								<div style="margin:10px;">
									<input type="checkbox" name="reloadSamples" value="1" /> Reload sample record if it already exists
								</div>
								<div style="margin:10px;">
									<input type="submit" name="action" value="Process Samples" />
									<input type="hidden" name="ulfilename" value="<?php echo $inquiryLoadManager->getUploadFileName(); ?>" />
								</div>
							</fieldset>
						</form>
					</div>
					<?php
				}
			}
			elseif($action == 'Process Samples'){
				echo '<ul>';
				$inquiryLoadManager->setUploadFileName($ulFileName);
				$inquiryLoadManager->setFieldMap($fieldMap);
				$samplePKArr = $inquiryLoadManager->uploadData();
				echo '</ul>';
				?>
				<fieldset>
					<legend><b>Samples Associated with Upload</b></legend>
					<?php
					foreach($samplePKArr as $shipmentID => $requestPK){
						echo '<div style="margin-left:10px">';
						if($requestPK) echo '<a href="samplelist.php?id='.$request_id.'">#'.$requestPK.'</a>';
						else echo 'Samples failed to load';
						echo '</div>';
					}
					?>
				</fieldset>
				<?php
			}
			else{
				?>
				<div>
					<form name="uploadform" action="sampleloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyUploadForm(this)">
						<fieldset style="width:90%;">
							<legend style="font-weight:bold;font-size:120%;">Sample Upload Form</legend>
							<div style="margin:10px;">
							</div>
							<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
							<div>
								<div style="margin:10px;">
									<input id="genuploadfile" name="uploadfile" type="file" size="40" />
								</div>
								<div style="margin:10px;">
									<input type="submit" name="action" value="Map Input File" />
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
else{
	?>
	<div style='font-weight:bold;margin:30px;'>
		You do not have permissions to upload samples to a request
	</div>
	<?php
}
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>