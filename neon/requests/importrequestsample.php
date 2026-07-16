<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/SampleRequestImport.php');
header("Content-Type: text/html; charset=".$CHARSET);

// redirect if not logged in
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/requests/importrequestsample.php');

$requestID = null;

if (isset($_REQUEST['requestID'])) {
    $requestID = filter_var($_REQUEST['requestID'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
} elseif (isset($_GET['id'])) {
    $requestID = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
}

if (!$requestID) {
    die('<h2 style="color:red;">Error: A valid requestID must be provided.</h2>');
}

$importType = isset($_REQUEST['importType']) ? filter_var($_REQUEST['importType'], FILTER_SANITIZE_NUMBER_INT) : 0;
$sampleType = isset($_POST['sampleType']) ? $_POST['sampleType'] : '';
$fileName = isset($_POST['fileName']) ? $_POST['fileName'] : '';
$action = isset($_POST['submitAction']) ? $_POST['submitAction'] : '';

$importManager = new SampleRequestImport();
$importManager->setImportType($importType);
$importManager->setRequestID($requestID);
$importManager->setFileName($fileName);

$isEditor = false;
if ($IS_ADMIN || array_key_exists('SuperAdmin', $USER_RIGHTS)) {
	$isEditor = true;
}
?>
<!DOCTYPE html>

<head>
	<title>Sample Request Import</title>
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script>
		function verifyFileSize(inputObj) {
			if (!window.FileReader) {
				//alert("The file API isn't supported on this browser yet.");
				return;
			}
			<?php
			$maxUpload = ini_get('upload_max_filesize');
			$maxUpload = str_replace("M", "000000", $maxUpload);
			if ($maxUpload > 10000000) $maxUpload = 10000000;
			echo 'var maxUpload = ' . $maxUpload . ";\n";
			?>
			var file = inputObj.files[0];
			if (file.size > maxUpload) {
				var msg = "Import File" + file.name + " (" + Math.round(file.size / 100000) / 10 + "File is too large" + (maxUpload / 1000000) + "MB).";
				alert(msg);
			}
		}

		function validateInitiateForm(f) {
			if (f.importFile.value == "") {
				alert("Select File");
				return false;
			}
			if (f.importType.value == "") {
				alert("Select Import Type");
				return false;
			}
			return true;
		}


		function validateMappingForm(f) {
			const sourceArr = [];
			const targetArr = [];

			const form_data = new FormData(f);

			for (const [key, value] of form_data.entries()) {
				if (key.startsWith("sf[")) {
					if (sourceArr.includes(value)) {
						alert("Duplicate Source Field: " + value);
						return false;
					}
					sourceArr.push(value);
				} else if (key.startsWith("tf[") && value !== "") {
					if (targetArr.includes(value)) {
						alert("Duplicate Target Field: " + value);
						return false;
					}
					targetArr.push(value);
				}
			}
			return true;
		}

	</script>
	<style>
		.formField-div {
			margin: 10px;
		}

		label {
			font-weight: bold;
		}

		fieldset {
			margin: 10px;
			padding: 10px;
		}

		legend {
			font-weight: bold;
		}

		.index-li {
			margin-left: 10px;
		}

		button {
			margin: 10px 15px
		}
	</style>
</head>

<body>
	
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div role="main" id="innertext">
		<h1 class="page-heading">Sample Importer</h1>
		<fieldset>
			<legend><b>Instructions</b></legend>
				<h4><b><u>Samples</u></b></h4>
					<p style="margin-left:10%; margin-right:10%; font-size:15px"> Loaded samples (think at the level of a manifest record) will be added to the request indicated in the box below. 
					<br><br>Each loaded sample must be associated with an identifier. This can include the occid (id field in download),catalogNumber, occurrenceID, or any otherCatalogNumber. However, sampleID/sampleTag or alternative sampleIDs are not recommended for use as these are not unique within the database and, thus, may correspond to multiple samples.
					<br><br>Samples will be loaded with a "status" of <b> pending fulfillment</b>. If for any reason that is not the current status of the samples
					with respect to the request, you should individually or batch edit that value after returning to the sample list.  
					<br> <br>
					
					The sample list follows a controlled vocabularly and has the following additional fields: <br><br></p>

					<table border="1">
						<tr>
							<th>Field</th>
							<th>Allowed Values</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>useType</td>
							<td>non-destructive</td>
							<td>No impact to future uses of the sample.</td>
						</tr>
						<tr>
							<td></td>
							<td>invasive</td>
							<td>No part of the sample is consumed, but changes to the sample impact future use.</td>
						</tr>
						<tr>
							<td></td>
							<td>consumptive</td>
							<td>A portion of the sample is consumed or destroyed.</td>
						</tr>
						<tr>
							<td></td>
							<td>destructive</td>
							<td>The entire sample is consumed or destroyed. </td>
						</tr>
						<tr>
							<td>substanceProvided</td>
							<td>whole sample</td>
							<td>The entire sample (think at the level of a <b>manifest record</b>) was sent to the researcher.</td>
						</tr>
						<tr>
							<td></td>
							<td>individual(s)</td>
							<td>One or more individuals were removed from a sample containing many individuals.</td>
						</tr>
						<tr>
							<td></td>
							<td>subsample/aliquot</td>
							<td>A subsample or aliquot was removed from the sample and sent to a researcher.</td>
						</tr>
						<tr>
							<td></td>
							<td>tissue/material sample</td>
							<td>A tissue or material sample was removed from the sample and provided to the researcher. Use "whole sample" when a sample that the Biorepository receives is already tissue. </td>
						</tr>
						<tr>
							<td></td>
							<td>image</td>
							<td> An image (or other media) taken from the sample was taken and used in research.</td>
						</tr>
						<tr>
							<td></td>
							<td>data</td>
							<td>The Biorepository or the researcher took additional data on the sample for research (or recorded internal) use.</td>
						</tr>
						<tr>
							<td>notes</td>
							<td>(free text)</td>
							<td>Any additional remarks. Use this to indicate what "tissue/material sample" was provided, the size of the aliquot, number of individuals, etc.</td>
						</tr>
					</table>
				<br>
				<h4><b><u>Material Samples</u></b></h4>

					<p style="margin-left:10%; margin-right:10%; font-size:15px"> Loaded material samples will be added to the request indicated in the box below. 
					<br><br>Each loaded material sample must be associated with an identifier. This can include the material sample catalogNumber, materialSampleID (guid), primary key of the material samples table, or material sample recordID.
					<br><br>Material samples will be loaded with a "status" of <b> pending fulfillment</b>. If for any reason that is not the current status of the material samples
					with respect to the request, you should individually or batch edit that value after returning to the material sample list.  
					<br><br>Note that material samples cannot be added until the parent sample has been added to the sample list.
					<br> <br>
					The material sample list follows a controlled vocabularly and has the following additional fields: <br><br></p>

					<table border="1">
						<tr>
							<th>Field</th>
							<th>Allowed Values</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>useType</td>
							<td>non-destructive</td>
							<td>No impact to future uses of the material sample.</td>
						</tr>
						<tr>
							<td></td>
							<td>invasive</td>
							<td>No part of the material sample is consumed, but changes to the sample impact future use.</td>
						</tr>
						<tr>
							<td></td>
							<td>consumptive</td>
							<td>A portion of the material sample is consumed or destroyed.</td>
						</tr>
						<tr>
							<td></td>
							<td>destructive</td>
							<td>The entire material sample is consumed or destroyed. </td>
						</tr>
						<td>sampleType</td>
							<td>(free text)</td>
							<td>Use this to indicate the type of material sample that was provided to the researcher. (Think at the level of the material sample record.)</td>
						</tr>
						<tr>
							<td>notes</td>
							<td>(free text)</td>
							<td>Any additional remarks. Use this to indicate what portion of the material sample was provided, the size of the aliquot, etc.</td>
						</tr>
					</table>
				<br>
		</fieldset>
		<h2><?= $importManager->getRequestMeta('id'); ?></h2>
		<?php
		if (!$isEditor) {
			echo '<h2>Not Authorized</h2>';
		}  else {
			$actionStatus = false;
			if ($action == 'importData') {
		?>
				<fieldset>
					<legend>Sample Loading Panel</legend>
					<?php
					echo '<ul>';
					echo '<li> Start Processing ' . $fileName . ' (' . date('Y-m-d H:i:s') . ')</li>';

						try {
							$success = $importManager->loadData($_POST, $SYMB_UID);

							echo '<li> Done Processing: Success (' . date('Y-m-d H:i:s') . ')</li>';

						} catch (Exception $e) {

							echo '<li>
								<strong style="color:red;">
									Import stopped due to an error. '
									. htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE)
									. '
								</strong>
							</li>';
						}
                        if ($importType == 1){
                            echo '<li><a href="samplelist.php?id=' . $requestID . '">Return to Sample List</a></li>';
                        } elseif($importType == 2){
                            echo '<li><a href="materialsamplelist.php?id=' . $requestID . '">Return to Material Sample List</a></li>';
                        }
					echo '</ul>';
					?>
				</fieldset>
				<?php
			} elseif ($action == 'initiateImport') {
				if ($actionStatus = $importManager->importFile()) {
					$importManager->setTargetFieldArr();
				?>
					<form name="mappingform" action="importrequestsample.php" method="post" onsubmit="return validateMappingForm(this)">
						<fieldset>
							<legend><b>Field Mapping</b></legend>
							<div class="formField-div">
								<?php
								echo $importManager->getFieldMappingTable();
								?>
							</div>
							<?php

							} elseif ($importType == 1) {
							?>
								<div class="formField-div">
									<input name="replace" type="checkbox" value="1">
									<label for="replace">Matching Identifiers</label>
								</div>
							<?php
							}
							?>
							<div style="margin:15px;">
								<input name="requestID" type="hidden" value="<?= $requestID; ?>">
								<input name="importType" type="hidden" value="<?= $importType ?>">
								<input name="fileName" type="hidden" value="<?= htmlspecialchars($importManager->getFileName(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>">
								<button name="submitAction" type="submit" value="importData">Import Data</button>
							</div>
						</fieldset>
					</form>
				<?php
				} else 'Intialize Import: ' . $importManager->getErrorMessage();
			}
			if (!$actionStatus) {
				?>
				<form name="initiateImportForm" action="importrequestsample.php" method="post" enctype="multipart/form-data" onsubmit="return validateInitiateForm(this)">
					<fieldset>
                        <?php
                            $requestID = $importManager->getRequestMeta('requestID');
                            $name      = $importManager->getRequestMeta('name');
                            $title     = $importManager->getRequestMeta('title');
                        ?>
						<legend>Import Samples For Request # <?php echo "Request #$requestID - $name - $title"; ?></legend>
						<div class="formField-div">
							<input name="importFile" type="file" onchange="verifyFileSize(this)" aria-label="Choose File" />
						</div>
						<div class="formField-div">
							<label for="importType">Import Type: </label>
							<select id="importType" name="importType" onchange="importTypeChanged(this)" aria-label="Import Type">
								<option value="">-------------------</option>
								<option value="1">Samples</option>
								<option value="2">Material Samples</option>
							</select>
						</div>
						<div class="formField-div">
							<input name="requestID" type="hidden" value="<?= $requestID ?>">
							<input name="MAX_FILE_SIZE" type="hidden" value="10000000" />
							<button name="submitAction" type="submit" value="initiateImport">Initialize Import</button>
						</div>
					</fieldset>
				</form>
		<?php
			}
		?>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>

</html>
