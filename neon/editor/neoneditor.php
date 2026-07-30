<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/neon/classes/NeonEditor.php');
if ($LANG_TAG != 'en' && !file_exists($SERVER_ROOT . '/content/lang/collections/admin/importextended.' . $LANG_TAG . '.php')) $LANG_TAG = 'en';
include_once($SERVER_ROOT . '/content/lang/collections/admin/importextended.' . $LANG_TAG . '.php');
header('Content-Type: text/html; charset=' . $CHARSET);

if (!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../neon/neoneditor/neoneditor.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$importType = array_key_exists('importType', $_REQUEST) ? filter_var($_REQUEST['importType'], FILTER_SANITIZE_NUMBER_INT) : 0;
$associationType = array_key_exists('associationType', $_POST) ? $_POST['associationType'] : '';
$createNew = array_key_exists('createNew', $_POST) ? filter_var($_POST['createNew'], FILTER_SANITIZE_NUMBER_INT) : 0;
$fileName = array_key_exists('fileName', $_POST) ? $_POST['fileName'] : '';
$action = array_key_exists('submitAction', $_POST) ? $_POST['submitAction'] : '';
$user = array_key_exists('user', $_POST) ? $_POST['user'] : '';

$importManager = new NeonEditor();
$importManager->setImportType($importType);
$importManager->setFileName($fileName);

$isEditor = false;
if ($IS_ADMIN || array_key_exists('SuperAdmin', $USER_RIGHTS)) {
	$isEditor = true;
}
?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">

<head>
	<title><?= 'NEON Data Editor'?> </title>
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
				var msg = "<?= $LANG['IMPORT_FILE'] ?>" + file.name + " (" + Math.round(file.size / 100000) / 10 + "<?= $LANG['IS_TOO_BIG'] ?>" + (maxUpload / 1000000) + "MB).";
				if (file.name.slice(-3) != "zip") msg = msg + "<?= $LANG['MAYBE_ZIP'] ?>";
				alert(msg);
			}
		}

		function validateInitiateForm(f) {
			if (f.importFile.value == "") {
				alert("<?= $LANG['SELECT_FILE'] ?>");
				return false;
			}
			if (f.importType.value == "") {
				alert("<?= $LANG['SELECT_IMPORT_TYPE'] ?>");
				return false;
			} else if (f.importType.value == 1 && f.associationType.value == "") {
				alert("<?= $LANG['SELECT_ASSOC_TYPE'] ?>");
				return false;
			}
			return true;
		}


		function validateMappingForm(f) {
			let sourceArr = [];
			let targetArr = [];
			let requiredFieldArr = [];
			<?php
			if ($associationType == 'resource' || $associationType == 'externalOccurrence') {
				echo 'requiredFieldArr["resourceUrl"] = 0; ';
			} elseif ($associationType == 'observational') {
				echo 'requiredFieldArr["verbatimSciname"] = 0; ';
			}
			?>
			let subjectIdentifierIsMapped = false;
			let identifierNameIsMapped = false;
			let identifierValueIsMapped = false;
			let objectIdentifierIsMapped = false;

			const form_data = new FormData(f);

			for (const [key, value] of form_data.entries()) {
				if (key.substring(0, 3) == "sf[") {
					if (sourceArr.indexOf(value) > -1) {
						alert("<?= $LANG['ERR_DUPLICATE_SOURCE'] ?>" + value + ")");
						return false;
					}
					sourceArr[sourceArr.length] = value;
				} else if (value != "") {
					if (key.substring(0, 3) == "tf[") {
						if (targetArr.indexOf(value) > -1) {
							alert("<?= $LANG['ERR_DUPLICATE_TARGET'] ?>" + value + ")");
							return false;
						}
						targetArr[targetArr.length] = value;
					}
				}
				if (key.substring(0, 3) == "tf[") {
					if (value == "catalognumber") {
						subjectIdentifierIsMapped = true;
					} else if (value == "othercatalognumbers") {
						subjectIdentifierIsMapped = true;
					} else if (value == "occurrenceid") {
						subjectIdentifierIsMapped = true;
					} else if (value == "occid") {
						subjectIdentifierIsMapped = true;
					}
					if (value == "identifiername") {
						identifierNameIsMapped = true;
					}
					if (value == 'identifiervalue') {
						identifierValueIsMapped = true;
					}
					<?php
					if ($associationType == 'internalOccurrence') {
					?>
						if (value == "object-catalognumber") {
							objectIdentifierIsMapped = true;
						} else if (value == "object-occurrenceid") {
							objectIdentifierIsMapped = true;
						} else if (value == "occidassociate") {
							objectIdentifierIsMapped = true;
						}
					<?php
					}
					?>
					for (const fieldName2 in requiredFieldArr) {
						if (value == fieldName2.toLowerCase()) requiredFieldArr[fieldName2] = 1;
					}
				}
			}
			if (!subjectIdentifierIsMapped && $importType==="5") {
				alert("<?= $LANG['SUBJECT_ID_REQUIRED'] ?>");
				return false;
			}
			if (!identifierNameIsMapped && $importType==="5") {
				alert("<?= $LANG['IDENTIFIER_NAME_REQUIRED'] ?>");
				return false;
			}
			if (!identifierValueIsMapped && $importType==="5") {
				alert("<?= $LANG['IDENTIFIER_ID_VALUE_REQUIRED'] ?>");
				return false;
			}
			<?php
			if ($associationType == 'internalOccurrence') {
			?>
				if (!objectIdentifierIsMapped) {
					alert("<?= $LANG['OBJECT_ID_REQUIRED'] ?>");
					return false;
				}
			<?php
			}
			?>
			if (f.relationship && f.relationship.value == "") {
				alert("<?= $LANG['SELECT_RELATIONSHIP'] ?>");
				return false;
			}
			for (const fieldName in requiredFieldArr) {
				if (requiredFieldArr[fieldName] == 0) {
					alert(fieldName + " is a required import field");
					return false;
				}
			}
			return true;
		}

		function importTypeChanged(selectElement) {
			let f = selectElement.form;
			if (selectElement.value == 1) {
				document.getElementById("associationType-div").style.display = "block";
			} else {
				document.getElementById("associationType-div").style.display = "none";
			}
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
		<a href="../../index.php"><?= $LANG['HOME'] ?></a> &gt;&gt;
		<a href="../index.php"><?= 'NEON Biorepository Tools' ?></a> &gt;&gt;
		<b><?= 'NEON Occurrence Editor'?></b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= 'NEON Data Importer/Editor' ?></h1>
		<fieldset>
			<legend><b>Instructions</b></legend>
			<details>
				<summary style="cursor:pointer; font-weight:bold; font-size:16px;">
					Show/Hide Import Instructions
				</summary>
					<p style="margin-left:10%; margin-right:10%; font-size:15px"> 
						This is the best tool for batch uploading and editing NEON sample data, including occurrence and extended data, associated with existing sample occurrence records. 
						All uploads/edits require a match to an existing sample occurrence record based on a <strong>unique</strong> identifier. When mapping fields, the central occurrence record
						identifier that must be matched with a target field beginning with "subject identifier" and can preferentially be the occid, occurrenceID, or catalogNumber 
						or any unique otherCatalogNumber value (e.g., barcode), if needed. Below are the available import types.
					</p>
					<h4><b><u>Occurrences</u></b></h4>
						<p style="margin-left:10%; margin-right:10%; font-size:15px">This import type is similar to a skeletal file upload, except that it is not specific to a single collection, can be used to update data, and can selectively prevent future data updates when harvesting NEON data. 
							Use the "Action" dropdown to select whether you would only like to only add data to empty fields or to also allow updates when data does exist. Check the box at the bottom if you would like data edits by NEON to overwrite these manual edits.
					</p>
					<h4><b><u>Associations</u></b></h4>
						<p style="margin-left:10%; margin-right:10%; font-size:15px"> Before loading associations, you first have to assign the association type. See the associated <a href="https://docs.symbiota.org/Collection_Manager_Guide/Importing_Uploading/linked_resources" target="_blank">Symbiota doc</a> for definitions and explanations of fields for each type. Types other than Internal Occurrence and Taxon Observation will be rare for the NEON portal.</a>
						<br> <br>
						<strong>Occurrence - Internal:</strong> For this association type, you must have a subject, as with all possible import types, in addition to an object identifier. The subject is associated to the object with the relationship (e.g., "subject sample hasHost object sample"). Relationship subtypes can optionally be chosen, such as to indicate a type of subsample. At this time, there is no option to batch update or delete associations via the front end.
						<br> <br>
						<strong>Taxon Observation:</strong> This association type operates in the same way as internal occurrence associations, except the object of the relationship is a scientific name instead of another sample in the portal.
						</p>
					<h4><b><u>Determinations</u></b></h4>
						<p style="margin-left:10%; margin-right:10%; font-size:15px"> ###
					</p>
					<h4><b><u>Genetic Links</u></b></h4>
						<p style="margin-left:10%; margin-right:10%; font-size:15px">This import type allows the upload or editing or linkages between samples and genetic or genomic data. 
							In addition to the sample identifier, an <strong>identifier</strong> for the genetic sequence information (e.g., identifier assigned by BOLD, biosample number in GenBank) and a <strong>resourcename</strong> (e.g., "Barcode of Life (BOLD)") are required. 
							We are using the locus field to describe what was sequenced, which may be a locus (e.g.,"Cytochrome Oxidase Subunit 1 5' Region") or a more general description (e.g., "metagenome"). ResourceUrl should be used to link to where the genetic or genomic data is externally stored.
					</p>
					<h4><b><u>Identifiers</u></b></h4>
						<p style="margin-left:10%; margin-right:10%; font-size:15px"> This tool allows you to batch add, update, or delete sample identifiers. In addition to an existing identifier, an identifierName (e.g., "Alternative NEON sampleID") and identifierValue are required. 
							Use the action "Batch Add or Update" to add new identifiers or update existing ones by additionally checking the box below, select batch delete to delete the identifier record with the identifierName, identifierValue, and sample combination. 
					</p>
					<h4><b><u>Material Samples</u></b></h4>
						<p style="margin-left:10%; margin-right:10%; font-size:15px"> ###
					<br>
					<h4><b><u>Media</u></b></h4>
						<p style="margin-left:10%; margin-right:10%; font-size:15px">This import type is described by the relevant <a href="https://docs.symbiota.org/Collection_Manager_Guide/Images/media_upload_url" target="_blank">Symbiota doc</a>. Note that under nearly all circumstances, images taken of Biorepository samples should have an <strong>owner</strong> value of "NEON (National Ecological Observatory Network) Biorepository" and a <strong>rights</strong> value of "https://creativecommons.org/licenses/by-sa/4.0/".
						The person who took the image/recorded the media should be credited in the "creator" field. </p>
			</details>
		</fieldset>
		<?php
		if (!$isEditor) {
			echo '<h2>' . $LANG['ERR_NOT_AUTH'] . '</h2>';
		} else {
			$actionStatus = false;
			if ($action == 'importData') {
		?>
				<fieldset>
					<legend><?= $LANG['ACTION_PANEL'] ?></legend>
					<?php
					$importManager->setCreateNewRecord($createNew);
					echo '<ul>';
					echo '<li>' . $LANG['STARTING_PROCESS'] . ' ' . $fileName . ' (' . date('Y-m-d H:i:s') . ')</li>';
					if ($importManager->loadData($_POST)) {
						echo '<li>' . $LANG['DONE_PROCESSING'] . ' (' . date('Y-m-d H:i:s') . ')</li>';
					}
					echo '</ul>';
					?>
				</fieldset>
				<?php
			} elseif ($action == 'initiateImport') {
				if ($actionStatus = $importManager->importFile()) {
					$importManager->setTargetFieldArr($associationType);
				?>
					<form name="mappingform" action="neoneditor.php" method="post" onsubmit="return validateMappingForm(this)">
						<fieldset>
							<legend><b><?= $LANG['FIELD_MAPPING'] ?></b></legend>
							<?php
							if ($associationType) {
							?>
								<div class="formField-div">
									<label for="associationType"><?= $LANG['ASSOCIATION_TYPE'] ?>:</label> <?= $associationType ?>
									<input name="associationType" type="hidden" value="<?= $associationType ?>">
								</div>
							<?php
							}
							if ($importType == 1) {
							?>
								<div class="formField-div">
									<label><?= $LANG['RELATIONSHIP'] ?>:</label>
									<select name="relationship">
										<option value="">-------------------</option>
										<?php
										$filter = '';
										//if($associationType == 'resource') $filter = 'associationType:resource';
										$relationshipArr = $importManager->getControlledVocabulary('omoccurassociations', 'relationship', $filter);
										foreach ($relationshipArr as $term => $display) {
											echo '<option value="' . $term . '">' . $display . '</option>';
										}
										?>
										<!-- <option value="">-------------------</option>
										<option value="DELETE"><?= $LANG['BATCH_DELETE'] ?></option> -->
									</select>
								</div>
								<div class="formField-div">
									<label><?= $LANG['REL_SUBTYPE'] ?>:</label>
									<select name="subType">
										<option value="">-------------------</option>
										<?php
										$relationshipArr = $importManager->getControlledVocabulary('omoccurassociations', 'subtype');
										foreach ($relationshipArr as $term => $display) {
											echo '<option value="' . $term . '">' . $display . '</option>';
										}
										?>
									</select>
								</div>
							<?php
							}
							if ($importType ==2){
								echo '<b>Note:</b> any new determinations with isCurrent=1 will become the only current determination for the occurrence. If isCurrent is not set in upload, new determinations will not be considered current';
							}
							?>
							<div class="formField-div">
								<?php
								echo $importManager->getFieldMappingTable();
								?>
							</div>
							<?php
							if ($importType == 3) {
							?>
								<div class="formField-div">
									<input name="createNew" type="checkbox" value="1" <?= ($createNew ? 'checked' : '') ?>>
									<label for="createNew"><?= $LANG['NEW_BLANK_RECORD'] ?></label>
								</div>
								<div class="formField-div">
									<label for="mediaUploadType"><?= $LANG['MEDIA_UPLOAD_TYPE'] ?>:</label>
									<select id="mediaUploadType" name="mediaUploadType" required >
										<option value="image"><?= $LANG['IMAGE_UPLOAD'] ?></option>
										<option value="audio"><?= $LANG['AUDIO_UPLOAD'] ?></option>
									</select>
								</div>
							<?php
							//} elseif ($importType == 1) {
							?>
								<!-- <div class="formField-div">
									<input name="replace" type="checkbox" value="1">
									<label for="replace"><?= $LANG['MATCHING_IDENTIFIERS'] ?></label>
								</div> -->
							<?php
							}
							if ($importType == 4){
							?>
								<div class="formField-div">
									<label for='action'><?= $LANG['ACTION'] ?>:</label>
									<select name="action" id='action'>
										<option value="add"><?= 'Batch add material samples' ?></option>
										<option value="update"><?= 'Batch update material samples' ?></option>
									</select>
								</div>
							<?php
							} 
							if ($importType == 5) {
							?>
								<div class="formField-div">
									<label for='action'><?= $LANG['ACTION'] ?>:</label>
									<select name="action" id='action'>
										<option value="add-or-update"><?= $LANG['BATCH_ADD_OR_UPDATE'] ?></option>
										<option value="delete"><?= $LANG['BATCH_DELETE'] ?></option>
									</select>
								</div>
								<div class="formField-div">
									<input name="replace-identifier" type="checkbox" value="1">
									<label for="replace-identifier"><?= $LANG['IDENTIFIER_UPDATE_OR_DELETE'] ?></label>
								</div>
							<?php
							}
							if ($importType == 6) {
							?>
								<div class="formField-div">
									<label for='action'><?= $LANG['ACTION'] ?>:</label>
									<select name="action" id='action'>
										<option value="add"><?= 'Batch add data to empty fields' ?></option>
										<option value="update"><?= 'Batch update data' ?></option>
									</select>
								</div>
								<div class="formField-div">
									<input name="allow-overwrite" type="checkbox" value="1">
									<label for="allow-overwrite"><?= 'Allow additions or edits to be overwritten during reharvest' ?></label>
								</div>
							<?php
							}
							if ($importType == 7) {
							?>
								<div class="formField-div">
									<label for='action'><?= $LANG['ACTION'] ?>:</label>
									<select name="action" id='action'>
										<option value="add"><?= 'Batch add links' ?></option>
										<option value="update"><?= 'Batch update links' ?></option>
									</select>
								</div>
								<div class="formField-div">
								<div class="formField-div">
									<input name="propagatederived" type="checkbox" value="1">
									<label for="propagatederived"><?= 'Propagate genetic links to all samples with "derivedFromSameIndividual" relationship' ?></label>
								</div>
								<div class="formField-div">
									<input name="propagateoriginating" type="checkbox" value="1">
									<label for="propagateoriginating"><?= 'Propagate genetic links to all samples with "originatingSampleOf"/"subsampleOf" relationship' ?></label>
								</div>
								</div>
							<?php
							}
							if ($importType == 2) {
							?>
								<div class="formField-div">
								<div class="formField-div">
									<input name="associatedoccurrences" type="checkbox" value="1">
									<label for="associatedoccurrences"><?= 'Propagate determinations to all samples with "derivedFromSameIndividual" relationship' ?></label>
								</div>
								</div>
							<?php
							}
							?>
							<div style="margin:15px;">
								<input name="importType" type="hidden" value="<?= $importType ?>">
								<input name="user" type="hidden" value="<?= $SYMB_UID ?>">
								<input name="fileName" type="hidden" value="<?= htmlspecialchars($importManager->getFileName(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>">
								<button name="submitAction" type="submit" value="importData"><?= $LANG['IMPORT_DATA'] ?></button>
							</div>
						</fieldset>
					</form>
				<?php
				} else echo $LANG['ERR_SETTING_IMPORT'] . ': ' . $importManager->getErrorMessage();
			}
			if (!$actionStatus) {
				?>
				<form name="initiateImportForm" action="neoneditor.php" method="post" enctype="multipart/form-data" onsubmit="return validateInitiateForm(this)">
					<fieldset>
						<legend><?= $LANG['INITIALIZE_IMPORT'] ?></legend>
						<div class="formField-div">
							<input name="importFile" type="file" onchange="verifyFileSize(this)" aria-label="<?php echo $LANG['CHOOSE_FILE'] ?>" />
						</div>
						<div class="formField-div">
							<label for="importType"><?= $LANG['IMPORT_TYPE'] ?>: </label>
							<select id="importType" name="importType" onchange="importTypeChanged(this)" aria-label="<?php echo $LANG['IMPORT_TYPE'] ?>">
								<option value="">-------------------</option>
								<option value="6"><?= 'Occurrence (or Skeletal)' ?></option>
								<option value="1"><?= $LANG['ASSOCIATIONS'] ?></option>
								<?php if ($IS_ADMIN) {
									echo '<option value="2">' . $LANG['DETERMINATIONS'] . '</option>';
								}
								?>
								<option value="7"><?= 'Genetic Links' ?></option>
								<option value="5"><?= $LANG['IDENTIFIERS'] ?></option>
								<?php
									echo '<option value="4">' . $LANG['MATERIAL_SAMPLE'] . '</option>';
								?>
								<option value="3"><?= 'Media' ?></option>
							</select>
						</div>
						<div id="associationType-div" class="formField-div" style="display:none">
							<label for="associationType"><?= $LANG['ASSOCIATION_TYPE'] ?>: </label>
							<select id="associationType" name="associationType" aria-label="<?php echo $LANG['ASSOCIATION_TYPE'] ?>">
								<option value="">-------------------</option>
								<option value="resource"><?= $LANG['RESOURCE_LINK'] ?></option>
								<option value="internalOccurrence"><?= $LANG['INTERNAL_OCCURRENCE'] ?></option>
								<option value="externalOccurrence"><?= $LANG['EXTERNAL_OCCURRENCE'] ?></option>
								<option value="observational"><?= $LANG['OBSERVATION'] ?></option>
							</select>
						</div>
						<div class="formField-div">
							<input name="MAX_FILE_SIZE" type="hidden" value="10000000" />
							<button name="submitAction" type="submit" value="initiateImport"><?= $LANG['INITIALIZE_IMPORT'] ?></button>
						</div>
					</fieldset>
				</form>
		<?php
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>

</html>
