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
    <div class="navpath">
        <a href="../../index.php">Home</a> &gt;&gt;
        <a href="../../neon/index.php">Management Tools</a> &gt;&gt;
        <a href="../../neon/requests/inquiries.php">Inquiry List</a> &gt;&gt;
        <a href="inquiryform.php?id=<?php echo $requestID; ?>">Inquiry Record</a> &gt;&gt;
        <a href="samplelist.php?id=<?php echo $requestID; ?>">Sample List</a> &gt;&gt;
        <b>Sample Importer</b>
    </div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Sample Importer</h1>
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
