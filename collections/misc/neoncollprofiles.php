<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/content/lang/collections/misc/collprofiles.' . $LANG_TAG . '.php');
include_once($SERVER_ROOT . '/classes/OccurrenceCollectionProfile.php');
header('Content-Type: text/html; charset=' . $CHARSET);
unset($_SESSION['editorquery']);

$collManager = new OccurrenceCollectionProfile();

$collid = isset($_REQUEST['collid']) ? $collManager->sanitizeInt($_REQUEST['collid']) : 0;
$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';
$eMode = array_key_exists('emode', $_REQUEST) ? $collManager->sanitizeInt($_REQUEST['emode']) : 0;

if ($eMode && !$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/misc/collprofiles.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collManager->setCollid($collid);

$collData = $collManager->getCollectionMetadata();
$datasetKey = $collManager->getDatasetKey();
$resourceJson = isset($collData[$collid]['resourcejson']) ? json_decode($collData[$collid]['resourcejson'], true) : [];
$dataProductIds = array_map(fn($item) => basename($item['url']), $resourceJson);
$encodedJson = json_encode(array_values($dataProductIds), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$editCode = 0;		//0 = no permissions; 1 = CollEditor; 2 = CollAdmin; 3 = SuperAdmin
if ($SYMB_UID) {
	if ($IS_ADMIN) {
		$editCode = 3;
	} else if ($collid) {
		if (array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin'])) $editCode = 2;
		elseif (array_key_exists('CollEditor', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollEditor'])) $editCode = 1;
	}
}
?>

<script>
  window.BiorepoCollectionData = '<?php echo $encodedJson; ?>';
</script>

<?php
$collData = $collManager->getCollectionMetadata();
?>

<html>

<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . ($collid && isset($collData[$collid]) ? $collData[$collid]['collectionname'] : ''); ?></title>
	<meta name="keywords" content="Natural history collections,<?php echo ($collid ? $collData[$collid]['collectionname'] : ''); ?>" />
	<meta http-equiv="Cache-control" content="no-cache, no-store, must-revalidate">
	<meta http-equiv="Pragma" content="no-cache">
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<link href="../../neon/css/mui-overrides.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="../../js/jquery.js?ver=20130917" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js?ver=20130917" type="text/javascript"></script>
	<script>
		function toggleById(target) {
			if (target != null) {
				var obj = document.getElementById(target);
				if (obj.style.display == "none" || obj.style.display == "") {
					obj.style.display = "block";
				} else {
					obj.style.display = "none";
				}
			}
			return false;
		}

		function copyCitation() {
			const citationText = document.getElementById("citation").innerText;
			const tempTextarea = document.createElement("textarea");
			tempTextarea.value = citationText;
			document.body.appendChild(tempTextarea);
			tempTextarea.select();
			document.execCommand("copy");
			document.body.removeChild(tempTextarea);

		}
		
		function downloadBib() {
		   const citationText = document.getElementById('citation').innerText;

		   const citationRegex = /(.+?)\s\((\d{4})\)\.\s(?:NEON Biorepository\s)?(.+?)\.\sOccurrence dataset\s(.+?)\saccessed\svia\sthe\s(.+?),\s(.+?)\son\s(\d{4}-\d{2}-\d{2})\./;
		   const match = citationText.match(citationRegex);
	   
		   if (match) {
			   const author = match[1];
			   const year = match[2];
			   const title = match[3];
			   const doi = match[4];
			   const portalName = match[5];
			   const portalUrl = match[6];
			   const accessDate = match[7];
	   
			   const bibtexEntry = `
@misc{${title.toLowerCase().replace(/\s+/g, '')}${accessDate},
	doi = {${doi}},
	author = {${author}},
	language = {en},
	title = {${title}},
	publisher = {National Ecological Observatory Network (NEON)},
	year = {${year}},
	note = {Accessed via ${portalName}, \\url{${portalUrl}}, on ${accessDate}},
	copyright = {Creative Commons Zero v1.0 Universal}
}
			   `.trim();

			   const blob = new Blob([bibtexEntry], { type: 'text/plain' });
			   const link = document.createElement('a');
			   link.href = URL.createObjectURL(blob);
			   link.download = `NEON-${title.replace(/[^a-zA-Z0-9]/g, '-')}-${accessDate}.bib`;
			   document.body.appendChild(link);
			   link.click();
			   document.body.removeChild(link);
		   }
        }
		
		function downloadRis() {
		   const citationText = document.getElementById('citation').innerText;
		   const abstractText = document.querySelector('#fulldescription-container p').innerText;

		   const citationRegex = /(.+?)\s\((\d{4})\)\.\s(?:NEON Biorepository\s)?(.+?)\.\sOccurrence dataset\s(.+?)\saccessed\svia\sthe\s(.+?),\s(.+?)\son\s(\d{4}-\d{2}-\d{2})\./;
		   const match = citationText.match(citationRegex);
	   
		   if (match) {
			   const author = match[1];
			   const year = match[2];
			   const title = match[3];
			   const doi = match[4];
			   const portalName = match[5];
			   const portalUrl = match[6];
			   const accessDate = match[7];
	   
				const risEntry = `
TY  - DATA
T1  - ${title}
AU  - ${author}
DO  - ${doi}
AB  - ${abstractText}
PY  - ${year}
PB  - National Ecological Observatory Network (NEON)
N1  - Accessed via ${portalName}, ${portalUrl}
LA  - en
DA  - ${accessDate}
ER  -
				`.trim();

			   const blob = new Blob([risEntry], { type: 'text/plain' });
			   const link = document.createElement('a');
			   link.href = URL.createObjectURL(blob);
			   link.download = `NEON-${title.replace(/[^a-zA-Z0-9]/g, '-')}-${accessDate}.ris`;
			   document.body.appendChild(link);
			   link.click();
			   document.body.removeChild(link);
		   }
        }
		
		document.addEventListener("DOMContentLoaded", function () {
			// Show tooltip
			function showTooltip(event) {
				const tooltipId = event.currentTarget.getAttribute("data-tooltip-id");
				const tooltip = document.getElementById(tooltipId);
				tooltip.style.display = "block";
			}
		
			// Hide tooltip
			function hideTooltip(event) {
				const tooltipId = event.currentTarget.getAttribute("data-tooltip-id");
				const tooltip = document.getElementById(tooltipId);
				tooltip.style.display = "none";
			}
		
			// Attach event listeners to all buttons with the class 'tooltip-button'
			const tooltipButtons = document.querySelectorAll(".tooltip-button");
			tooltipButtons.forEach(button => {
				button.addEventListener("mouseover", showTooltip);
				button.addEventListener("mouseout", hideTooltip);
			});
			
			let descriptionContainer = document.getElementById('fulldescription-container');
			let imgElements = descriptionContainer.querySelectorAll('img');
			let collimagesContainer = document.getElementById('collimages');
			imgElements.forEach(img => {
				collimagesContainer.appendChild(img);
			});
		});
	</script>
	<style type="text/css">
		.field-div {
			margin: 10px 0px;
			clear: both
		}

		.label {
			font-weight: bold;
		}
		
		#collimages {
			max-height: 800px;
			display: flex;
			flex-direction: column;
			justify-content: space-evenly;
			align-items: center;
		}
		#collimages img {
			height: auto;
			width: auto !important;
			object-fit: contain;
		}
		
	</style>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
      body {
        font-family: 'Open Sans', sans-serif;
      }
    </style>
</head>

<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collprofilesMenu) ? $collections_misc_collprofilesMenu : true);
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?php echo (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home'); ?></a> &gt;&gt;
		<a href="../index.php"><?php echo (isset($LANG['COLLECTION_SEARCH']) ? $LANG['COLLECTION_SEARCH'] : 'Collection Search Page'); ?></a> &gt;&gt;
		<b><?php echo (isset($LANG['COLL_PROFILE']) ? $LANG['COLL_PROFILE'] : 'Collection Profile'); ?></b>
	</div>
	<div id="innertext">
		<?php
		if ($editCode > 1) {
			if ($action == 'UpdateStatistics') {
				echo '<h2> ' . (isset($LANG['UPDATE_STATISTICS']) ? $LANG['UPDATE_STATISTICS'] : 'Updating statistics related to this collection...') . '</h2>';
				$collManager->updateStatistics(true);
				echo '<hr/>';
			}
		}
		if ($editCode && $collid) {
		?>
			<div style="float:right;margin:3px;cursor:pointer;" onclick="toggleById('controlpanel');" title="<?php echo (isset($LANG['TOGGLE_MAN']) ? $LANG['TOGGLE_MAN'] : 'Toggle Manager\'s Control Panel'); ?>">
				<img style='border:0px;' src='../../images/edit.png' />
			</div>
			<?php
		}
		if ($collid && isset($collData[$collid])) {
			$collData = $collData[$collid];
			$codeStr = ' (' . $collData['institutioncode'];
			if ($collData['collectioncode']) $codeStr .= '-' . $collData['collectioncode'];
			$codeStr .= ')';
			$_SESSION['colldata'] = $collData;
			if ($editCode) {
			?>
				<div id="controlpanel" style="clear:both;display:<?php echo ($eMode ? 'block' : 'none'); ?>;">
					<fieldset style="padding:10px;padding-left:25px;">
						<legend><b><?php echo (isset($LANG['DAT_EDIT']) ? $LANG['DAT_EDIT'] : 'Data Editor Control Panel'); ?></b></legend>
						<fieldset style="float:right;margin:5px;min-inline-size:0;padding:0.35em 0.625em 0.75em;" title="Quick Search">
							<legend><b><?php echo (isset($LANG['QUICK_SEARCH']) ? $LANG['QUICK_SEARCH'] : 'Quick Search'); ?></b></legend>
							<b><?php echo (isset($LANG['CAT_NUM']) ? $LANG['CAT_NUM'] : 'Catalog Number'); ?></b><br />
							<form name="quicksearch" action="../editor/occurrenceeditor.php" method="post">
								<input name="q_catalognumber" type="text" style="border:1px solid #d0d0d0 !important;"/>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="occindex" type="hidden" value="0" />
							</form>
						</fieldset>
						<ul>
							<?php
							if (stripos($collData['colltype'], 'observation') !== false) {
							?>
								<li>
									<a href="../editor/observationsubmit.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['SUBMIT_IMAGE_V']) ? $LANG['SUBMIT_IMAGE_V'] : 'Submit an Image Voucher (observation supported by a photo)'); ?>
									</a>
								</li>
							<?php
							}
							?>
							<li>
								<a href="../editor/occurrenceeditor.php?gotomode=1&collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['ADD_NEW_OCCUR']) ? $LANG['ADD_NEW_OCCUR'] : 'Add New Occurrence Record'); ?>
								</a>
							</li>
<!--							<?php
							if ($collData['colltype'] == 'Preserved Specimens') {
							?>
								<li style="margin-left:10px">
									<a href="../editor/imageoccursubmit.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['CREATE_NEW_REC']) ? $LANG['CREATE_NEW_REC'] : 'Create New Records Using Image'); ?>
									</a>
								</li>
								<li style="margin-left:10px">
									<a href="../editor/skeletalsubmit.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['SKELETAL']) ? $LANG['SKELETAL'] : 'Add Skeletal Records'); ?>
									</a>
								</li>
							<?php
							}
							?>-->
							<li>
								<a href="../editor/occurrencetabledisplay.php?displayquery=1&collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['EDIT_EXISTING']) ? $LANG['EDIT_EXISTING'] : 'Edit Existing Occurrence Records'); ?>
								</a>
							</li>
							<li>
								<a href="../editor/batchdeterminations.php?collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['ADD_BATCH_DETER']) ? $LANG['ADD_BATCH_DETER'] : 'Add Batch Determinations/Nomenclatural Adjustments'); ?>
								</a>
							</li>
							<li>
								<a href="../reports/labelmanager.php?collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['PRINT_LABELS']) ? $LANG['PRINT_LABELS'] : 'Print Specimen Labels'); ?>
								</a>
							</li>
<!--							<li>
								<a href="../reports/annotationmanager.php?collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['PRINT_ANNOTATIONS']) ? $LANG['PRINT_ANNOTATIONS'] : 'Print Annotations Labels'); ?>
								</a>
							</li>-->
							<?php
							if ($collManager->traitCodingActivated()) {
							?>
								<!--<li>-->
								<!--	<a href="#" onclick="$('li.traitItem').show(); return false;">-->
								<!--		<?php echo (isset($LANG['TRAIT_CODING_TOOLS']) ? $LANG['TRAIT_CODING_TOOLS'] : 'Occurrence Trait Coding Tools'); ?>-->
								<!--	</a>-->
								<!--</li>-->
								<!--<li class="traitItem" style="margin-left:10px;display:none;">-->
								<!--	<a href="../traitattr/occurattributes.php?collid=<?php echo $collid; ?>">-->
								<!--		<?php echo (isset($LANG['TRAIT_CODING']) ? $LANG['TRAIT_CODING'] : 'Trait Coding from Images'); ?>-->
								<!--	</a>-->
								<!--</li>-->
								<!--<li class="traitItem" style="margin-left:10px;display:none;">-->
								<!--	<a href="../traitattr/attributemining.php?collid=<?php echo $collid; ?>">-->
								<!--		<?php echo (isset($LANG['TRAIT_MINING']) ? $LANG['TRAIT_MINING'] : 'Trait Mining from Verbatim Text'); ?>-->
								<!--	</a>-->
								<!--</li>-->
							<?php
							}
							?>
<!--							<li>
								<a href="../georef/batchgeoreftool.php?collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['BATCH_GEOREF']) ? $LANG['BATCH_GEOREF'] : 'Batch Georeference Specimens'); ?>
								</a>
							</li>-->
							<?php
							if ($collData['colltype'] == 'Preserved Specimens') {
							?>
								<li>
									<a href="../loans/index.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['LOAN_MANAGEMENT']) ? $LANG['LOAN_MANAGEMENT'] : 'Loan Management'); ?>
									</a>
								</li>
							<?php
							}
							?>
						</ul>
					</fieldset>
					<?php
					if ($editCode > 1) {
					?>
						<fieldset style="padding:10px;padding-left:25px;">
							<legend><b><?php echo (isset($LANG['ADMIN_CONTROL']) ? $LANG['ADMIN_CONTROL'] : 'Administration Control Panel'); ?></b></legend>
							<ul>
								<!--<li>-->
								<!--	<a href="commentlist.php?collid=<?php echo $collid; ?>">-->
								<!--		<?php echo (isset($LANG['VIEW_COMMENTS']) ? $LANG['VIEW_COMMENTS'] : 'View Posted Comments'); ?>-->
								<!--	</a>-->
								<!--	<?php if ($commCnt = $collManager->unreviewedCommentsExist()) echo '- <span style="color:orange">' . $commCnt . ' ' . (isset($LANG['UNREVIEWED_COMMENTS']) ? $LANG['UNREVIEWED_COMMENTS'] : 'unreviewed comments') . '</span>'; ?>-->
								<!--</li>-->
								<li>
									<a href="collmetadata.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['EDIT_META']) ? $LANG['EDIT_META'] : 'Edit Metadata'); ?>
									</a>
								</li>
								<!--
								<li>
									<a href="" onclick="$('li.metadataItem').show(); return false;"  >
										<?php echo (isset($LANG['OPEN_META']) ? $LANG['OPEN_META'] : 'Open Metadata'); ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="collmetadata.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['EDIT_META']) ? $LANG['EDIT_META'] : 'Edit Metadata'); ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="colladdress.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['EDIT_ADDRESS']) ? $LANG['EDIT_ADDRESS'] : 'Edit Mailing Address'); ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="collproperties.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['EDIT_COLL_PROPS']) ? $LANG['EDIT_COLL_PROPS'] : 'Special Properties'); ?>
									</a>
								</li>
								 -->
								<li>
									<a href="collpermissions.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['MANAGE_PERMISSIONS']) ? $LANG['MANAGE_PERMISSIONS'] : 'Manage Permissions'); ?>
									</a>
								</li>
								<li>
									<a href="#" onclick="$('li.importItem').show(); return false;">
										<?php echo (isset($LANG['IMPORT_SPECIMEN']) ? $LANG['IMPORT_SPECIMEN'] : 'Import/Update Specimen Records'); ?>
									</a>
								</li>
								<li class="importItem" style="margin-left:10px;display:none;">
									<a href="../admin/specupload.php?uploadtype=7&collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['SKELETAL_FILE_IMPORT']) ? $LANG['SKELETAL_FILE_IMPORT'] : 'Skeletal File Import'); ?>
									</a>
								</li>
								<li class="importItem" style="margin-left:10px;display:none">
									<a href="../admin/specupload.php?uploadtype=3&collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['TEXT_FILE_IMPORT']) ? $LANG['TEXT_FILE_IMPORT'] : 'Text File Import'); ?>
									</a>
								</li>
								<li class="importItem" style="margin-left:10px;display:none;">
									<a href="../admin/specupload.php?uploadtype=6&collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['DWCA_IMPORT']) ? $LANG['DWCA_IMPORT'] : 'DwC-Archive Import'); ?>
									</a>
								</li>
								<li class="importItem" style="margin-left:10px;display:none;">
									<a href="../admin/specupload.php?uploadtype=8&collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['IPT_IMPORT']) ? $LANG['IPT_IMPORT'] : 'IPT Import'); ?>
									</a>
								</li>
								<li class="importItem" style="margin-left:10px;display:none;">
									<a href="../admin/specupload.php?uploadtype=9&collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['NFN_IMPORT']) ? $LANG['NFN_IMPORT'] : 'Notes from Nature Import'); ?>
									</a>
								</li>
								<li class="importItem" style="margin-left:10px;display:none;">
									<a href="../admin/specuploadmanagement.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['IMPORT_PROFILES']) ? $LANG['IMPORT_PROFILES'] : 'Saved Import Profiles'); ?>
									</a>
								</li>
								<li class="importItem" style="margin-left:10px;display:none;">
									<a href="../admin/specuploadmanagement.php?action=addprofile&collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['CREATE_PROFILE']) ? $LANG['CREATE_PROFILE'] : 'Create a new Import Profile'); ?>
									</a>
								</li>
								<?php
								if ($collData['colltype'] != 'General Observations') {
									if ($collData['managementtype'] != 'Aggregate') {
								?>
										<li>
											<a href="../specprocessor/index.php?collid=<?php echo $collid; ?>">
												<?php echo (isset($LANG['PROCESSING_TOOLBOX']) ? $LANG['PROCESSING_TOOLBOX'] : 'Processing Toolbox'); ?>
											</a>
										</li>
										<li>
											<a href="../datasets/datapublisher.php?collid=<?php echo $collid; ?>">
												<?php echo (isset($LANG['DARWIN_CORE_PUB']) ? $LANG['DARWIN_CORE_PUB'] : 'Darwin Core Archive Publishing'); ?>
											</a>
										</li>
									<?php
									}
									?>
									<li>
										<a href="../editor/editreviewer.php?collid=<?php echo $collid; ?>">
											<?php echo (isset($LANG['REVIEW_SPEC_EDITS']) ? $LANG['REVIEW_SPEC_EDITS'] : 'Review/Verify Occurrence Edits'); ?>
										</a>
									</li>
									<!--
									<li>
										<a href="../reports/accessreport.php?collid=<?php echo $collid; ?>">
											<?php echo (isset($LANG['ACCESS_REPORT']) ? $LANG['ACCESS_REPORT'] : 'View Access Statistics'); ?>
										</a>
									</li>
									 -->
								<?php
								}
								if (!empty($ACTIVATE_DUPLICATES)) {
								?>
									<li>
										<a href="../datasets/duplicatemanager.php?collid=<?php echo $collid; ?>">
											<?php echo (isset($LANG['DUP_CLUSTER']) ? $LANG['DUP_CLUSTER'] : 'Duplicate Clustering'); ?>
										</a>
									</li>
								<?php
								}
								?>
								<li>
									<?php echo (isset($LANG['MAINTENANCE_TASKS']) ? $LANG['MAINTENANCE_TASKS'] : 'General Maintenance Tasks'); ?>
								</li>
								<?php
								if ($collData['colltype'] != 'General Observations') {
								?>
									<!--<li style="margin-left:10px;">-->
									<!--	<a href="../cleaning/index.php?obsuid=0&collid=<?php echo $collid; ?>">-->
									<!--		<?php echo (isset($LANG['DATA_CLEANING']) ? $LANG['DATA_CLEANING'] : 'Data Cleaning Tools'); ?>-->
									<!--	</a>-->
									<!--</li>-->
								<?php
								}
								?>
								<li style="margin-left:10px;">
									<a href="#" onclick="newWindow = window.open('collbackup.php?collid=<?php echo $collid; ?>','bucollid','scrollbars=1,toolbar=0,resizable=1,width=600,height=250,left=20,top=20');">
										<?php echo (isset($LANG['BACKUP_DATA_FILE']) ? $LANG['BACKUP_DATA_FILE'] : 'Download Backup Data File'); ?>
									</a>
								</li>
								<?php
								if ($collData['managementtype'] == 'Live Data') {
								?>
									<li style="margin-left:10px;">
										<a href="../admin/restorebackup.php?collid=<?php echo $collid; ?>">
											<?php echo (isset($LANG['RESTORE_BACKUP']) ? $LANG['RESTORE_BACKUP'] : 'Restore Backup File'); ?>
										</a>
									</li>
								<?php
								}
								?>
								<!--
								<li style="margin-left:10px;">
									<a href="../../imagelib/admin/igsnmapper.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['GUID_MANAGEMENT']) ? $LANG['GUID_MANAGEMENT'] : 'IGSN GUID Management'); ?>
									</a>
								</li>
								 -->
								<!--<li style="margin-left:10px;">-->
								<!--	<a href="../../imagelib/admin/thumbnailbuilder.php?collid=<?php echo $collid; ?>">-->
								<!--		<?php echo (isset($LANG['THUMBNAIL_MAINTENANCE']) ? $LANG['THUMBNAIL_MAINTENANCE'] : 'Thumbnail Maintenance'); ?>-->
								<!--	</a>-->
								<!--</li>-->
								<li style="margin-left:10px;">
									<a href="collprofiles.php?collid=<?php echo $collid; ?>&action=UpdateStatistics">
										<?php echo (isset($LANG['UPDATE_STATS']) ? $LANG['UPDATE_STATS'] : 'Update Statistics'); ?>
									</a>
								</li>
							</ul>
						</fieldset>
					<?php
					}
					?>
				</div>
			<?php
			}
			?>
			<div class="mb-6">
				<?php
				echo '<h1 class="text-3xl font-bold text-left mb-4">' . $collData['collectionname'] . '</h1>';
				?>
				<div class="flex justify-left space-x-3 mt-4">
					<?php
					if ($datasetKey) {
						echo '<iframe src="https://www.gbif.org/api/widgets/literature/button?gbifDatasetKey=' . $datasetKey . '" scrolling="no" frameborder="0" allowtransparency="true" allowfullscreen="false" style="width: 140px; height: 24px;"></iframe>';
						// Check if the Bionomia badge has been created yet - typically lags ~2 weeks behind GBIF publication
						$bionomiaUrl = 'https://api.bionomia.net/dataset/' . $datasetKey . '/badge.svg';
						$ch = curl_init($bionomiaUrl);
						curl_setopt($ch, CURLOPT_NOBODY, true);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
						curl_exec($ch);
						$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						curl_close($ch);
						// Check the response code - display image if exists
						if ($responseCode === 200) {			
							echo '<a href="https://bionomia.net/dataset/' . $datasetKey . '"><img src="' . $bionomiaUrl . '" alt="Bionomia dataset badge" style="width:262px; height:24px;"></a>';
						}
					}
					?>
					<a href="<?php echo $CLIENT_ROOT . '/collections/list.php?db=' . $collid; ?>">
						<button class="" style="height: 24px;">Browse Collection</button>
					</a>
					
				</div>
			</div>
			<div class="grid grid-cols-1 gap-4 mb-6">
				<div>
					<h2 class="text-xl font-semibold mb-2">Contact Information</h2>
					<?php
					$contacts = json_decode($collData["contactjson"], true);
					foreach ($contacts as $contact) {
						$output = '<p class="mb-4">';
						if (!empty($contact["role"])) {
							$output .= htmlspecialchars($contact["role"]) . ': ';
						}
						$output .= htmlspecialchars($contact["firstName"]) . ' ' . htmlspecialchars($contact["lastName"]);
						if (!empty($contact["orcid"])) {
							$output .= ' (ORCID ID: <a href="https://orcid.org/' . htmlspecialchars($contact["orcid"]) . '" target="_blank">' . htmlspecialchars($contact["orcid"]) . '</a>)';
						}
						$output .= '</p>';
						echo $output;
					}
					?>
					<a href="https://www.neonscience.org/about/contact-neon-biorepository">
						<button class="tooltip-button bg-white-300 text-blue-800 py-2 px-4 rounded-none inline-flex items-center border-2 border-blue-800" style="height: 24px;">
							Contact the Biorepository
						</button>
					</a>
				</div>
			</div>
			
			<div class="grid grid-cols-1 gap-4 mb-6">
				<div id="fulldescription-container">
					<h2 class="text-xl font-semibold mb-2">About Collection</h2>
					<?php
					echo $collData["fulldescription"];
					?>
				</div>
			</div>
	
			<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
				<div id="collimages" class="lg:col-span-1">
				</div>

				<?php
				//Collection Statistics
				$statsArr = $collManager->getBasicStats();
				$georefPerc = 0;
				if ($statsArr['georefcnt'] && $statsArr['recordcnt']) $georefPerc = (100 * ($statsArr['georefcnt'] / $statsArr['recordcnt']));
				?>
				<div class="lg:col-span-1 flex flex-col justify-around">
					<div>
						<h2 class="text-xl font-semibold mb-2"><?php echo (isset($LANG['COLL_STATISTICS']) ? $LANG['COLL_STATISTICS'] : 'Collection Statistics'); ?></h2>
						<ul class="list-disc pl-6">
							<li><?php echo number_format($statsArr["recordcnt"]) . ' ' . (isset($LANG['SPECIMEN_RECORDS']) ? $LANG['SPECIMEN_RECORDS'] : 'specimen records'); ?></li>
							<li><?php echo ($statsArr['georefcnt'] ? number_format($statsArr['georefcnt']) : 0) . ($georefPerc ? " (" . ($georefPerc > 1 ? round($georefPerc) : round($georefPerc, 2)) . "%)" : '') . ' ' . (isset($LANG['GEOREFERENCED']) ? $LANG['GEOREFERENCED'] : 'georeferenced'); ?></li>
							<?php
							$extrastatsArr = array();
							if ($statsArr['dynamicProperties']) $extrastatsArr = json_decode($statsArr['dynamicProperties'], true);
							if ($extrastatsArr) {
								if ($extrastatsArr['imgcnt']) {
									$imgSpecCnt = $extrastatsArr['imgcnt'];
									$imgCnt = 0;
									if (strpos($imgSpecCnt, ':')) {
										$imgCntArr = explode(':', $imgSpecCnt);
										$imgCnt = $imgCntArr[0];
										$imgSpecCnt = $imgCntArr[1];
									}
									if ($imgSpecCnt) {
										$imgPerc = 0;
										if ($statsArr['recordcnt']) $imgPerc = (100 * ($imgSpecCnt / $statsArr['recordcnt']));
										echo '<li>';
										echo number_format($imgSpecCnt) . ($imgPerc ? " (" . ($imgPerc > 1 ? round($imgPerc) : round($imgPerc, 2)) . "%)" : '') . ' ' . (isset($LANG['WITH_IMAGES']) ? $LANG['WITH_IMAGES'] : 'with images');
										if ($imgCnt) echo ' (' . number_format($imgCnt) . ' ' . (isset($LANG['TOTAL_IMAGES']) ? $LANG['TOTAL_IMAGES'] : 'total images') . ')';
										echo '</li>';
									}
								}
								$genRefStr = '';
								if (isset($extrastatsArr['gencnt']) && $extrastatsArr['gencnt']) $genRefStr = number_format($extrastatsArr['gencnt']) . ' ' . (isset($LANG['GENBANK_REF']) ? $LANG['GENBANK_REF'] : 'GenBank') . ', ';
								if (isset($extrastatsArr['boldcnt']) && $extrastatsArr['boldcnt']) $genRefStr .= number_format($extrastatsArr['boldcnt']) . ' ' . (isset($LANG['BOLD_REF']) ? $LANG['BOLD_REF'] : 'BOLD') . ', ';
								if (isset($extrastatsArr['geneticcnt']) && $extrastatsArr['geneticcnt']) $genRefStr .= number_format($extrastatsArr['geneticcnt']) . ' ' . (isset($LANG['OTHER_GENETIC_REF']) ? $LANG['OTHER_GENETIC_REF'] : 'other');
								if ($genRefStr) echo '<li>' . trim($genRefStr, ' ,') . ' ' . (isset($LANG['GENETIC_REF']) ? $LANG['GENETIC_REF'] : 'genetic references') . '</li>';
								if (isset($extrastatsArr['refcnt']) && $extrastatsArr['refcnt']) echo '<li>' . number_format($extrastatsArr['refcnt']) . ' ' . (isset($LANG['PUB_REFS']) ? $LANG['PUB_REFS'] : 'publication references') . '</li>';
								if (isset($extrastatsArr['SpecimensCountID']) && $extrastatsArr['SpecimensCountID']) {
									$spidPerc = (100 * ($extrastatsArr['SpecimensCountID'] / $statsArr['recordcnt']));
									echo '<li>' . number_format($extrastatsArr['SpecimensCountID']) . ($spidPerc ? " (" . ($spidPerc > 1 ? round($spidPerc) : round($spidPerc, 2)) . "%)" : '') . ' ' . (isset($LANG['IDED_TO_SPECIES']) ? $LANG['IDED_TO_SPECIES'] : 'identified to species') . '</li>';
								}
							}
							if (isset($statsArr['familycnt']) && $statsArr['familycnt']) echo '<li>' . number_format($statsArr['familycnt']) . ' ' . (isset($LANG['FAMILIES']) ? $LANG['FAMILIES'] : 'families') . '</li>';
							if (isset($statsArr['genuscnt']) && $statsArr['genuscnt']) echo '<li>' . number_format($statsArr['genuscnt']) . ' ' . (isset($LANG['GENERA']) ? $LANG['GENERA'] : 'genera') . '</li>';
							if (isset($statsArr['speciescnt']) && $statsArr['speciescnt']) echo '<li>' . number_format($statsArr['speciescnt']) . ' ' . (isset($LANG['SPECIES']) ? $LANG['SPECIES'] : 'species') . '</li>';
							if ($extrastatsArr && $extrastatsArr['TotalTaxaCount']) echo '<li>' . number_format($extrastatsArr['TotalTaxaCount']) . ' ' . (isset($LANG['TOTAL_TAXA']) ? $LANG['TOTAL_TAXA'] : 'total taxa (including subsp. and var.)') . '</li>';
							//if($extrastatsArr&&$extrastatsArr['TypeCount']) echo '<li>'.number_format($extrastatsArr['TypeCount']).' '.(isset($LANG['TYPE_SPECIMENS'])?$LANG['TYPE_SPECIMENS']:'type specimens').'</li>';
							?>
						</ul>
					</div>
					<div class="border-t-2 border-gray-200 mt-6 pt-4">
						<h2 class="text-xl font-semibold mb-2">Citation</h2>
						<p style="padding:16px"><strong>Please use the appropriate citation in your publications. See <a href="/neon/misc/cite.php">Acknowledging and Citing the Biorepository</a> for more info.</strong></p>
						<?php
						if (file_exists($SERVER_ROOT . '/includes/citationcollection.php')) {
							echo '<div style="border: 1px solid rgba(0, 0, 0, 0.12); padding: 16px;"">
							<div id="citation" style="font-family: monospace; padding: 16px; font-size:large">';
							// If GBIF dataset key is available, fetch GBIF format from API
							if ($collData['publishtogbif'] && $datasetKey && file_exists($SERVER_ROOT . '/includes/citationgbif.php')) {
								$gbifUrl = 'http://api.gbif.org/v1/dataset/' . $datasetKey;
								$responseData = json_decode(file_get_contents($gbifUrl));
								$collData['gbiftitle'] = $responseData->title;
								$collData['doi'] = $responseData->doi;
								$_SESSION['colldata'] = $collData;
								include($SERVER_ROOT . '/includes/citationgbif.php');
							} elseif ($collData['dynamicproperties'] && array_key_exists('edi', json_decode($collData['dynamicproperties'], true))) { // If EDI DOI is available, fetch EDI format from API
								$doiNum = json_decode($collData['dynamicproperties'], true)['edi'];
								if (substr($doiNum, 0, 4) === 'doi:') {
									$doiNum = substr($doiNum, 4);
								}
								$collData['doi'] = $doiNum;
								$_SESSION['colldata'] = $collData;
								include($SERVER_ROOT . '/includes/citationedi.php');
							} else {
								include($SERVER_ROOT . '/includes/citationcollection.php');
							}
							echo '</div>';
						}
						?>
							<div class="flex space-x-2">
								<button id="copyButton" data-tooltip-id="tooltip-copy" 
								  onclick="copyCitation()" 
								  class="Mui tooltip-button bg-white-300 text-[#0073cf] py-2 px-4 rounded-[2px] inline-flex items-center border-2 border-[#0073cf] font-[600] leading-[1.75] tracking-[0.06em]">
								  <i class="fas fa-copy mr-2"></i>
								  <span>COPY</span>
								  <span id="tooltip-copy" style="display:none; width:20rem; transform: translate(-15px, 65px);" 
									class="tooltip absolute bg-gray-100 text-black border border-black p-2 rounded-none text-base font-normal">
									Click to copy the above plain text citation to the clipboard
								  </span>
								</button>
								<button id="bibButton" data-tooltip-id="tooltip-bib" onclick="downloadBib()" class="Mui tooltip-button bg-white-300 text-[#0073cf] py-2 px-4 rounded-[2px] inline-flex items-center border-2 border-[#0073cf] font-[600] leading-[1.75] tracking-[0.06em]">
									<svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path></svg>
									<span>DOWNLOAD (BIBTEX)</span>
									<span id="tooltip-bib" style="display:none; width:20rem; transform: translate(-15px, 65px);" class="tooltip absolute bg-gray-100 text-black border border-black p-2 rounded-none text-base font-normal">
										Click to download the citation as a file in BibTex format
									</span>
								</button>
								<button id="risButton" data-tooltip-id="tooltip-ris" onclick="downloadRis()" class="Mui tooltip-button bg-white-300 text-[#0073cf] py-2 px-4 rounded-[2px] inline-flex items-center border-2 border-[#0073cf] font-[600] leading-[1.75] tracking-[0.06em]">
									<svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path></svg>
									<span>DOWNLOAD (RIS)</span>
									<span id="tooltip-ris" style="display:none; width:25rem; transform: translate(-15px, 65px);" class="tooltip absolute bg-gray-100 text-black border border-black p-2 rounded-none text-base font-normal">
										Click to download the citation as a file in Research Information Systems (RIS) format
									</span>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<div class="border-t-2 border-gray-200 mt-6 pt-4">
			  <h2 class="text-xl mb-2">External Links</h2>
			  <div class="mb-4">
				<?php
				if (isset($collData['resourcejson'])) {
					if ($resourceArr = json_decode($collData['resourcejson'], true)) {
						echo '<div class="mb-4">';
				
						foreach ($resourceArr as $rArr) {
							$title = $rArr['title'][$LANG_TAG] ?? "No Title Available";
							$url = htmlspecialchars($rArr['url']);
							$fileNumber = basename($rArr['url']);
				
							echo '<div class="MuiListItem-container">';
							echo '    <div class="MuiListItem-root MuiListItem-gutters MuiListItem-secondaryAction" style="padding-left: 8px">';
							echo '        <div class="MuiListItemIcon-root" style="min-width: 40px">';
							echo '            <svg class="MuiSvgIcon-root" focusable="false" viewBox="0 0 64 64" aria-hidden="true">';
							echo '                <g>';
							echo '                    <g>';
							echo '                        <path d="M35.521,41.288c-3.422,0-6.64-1.333-9.06-3.753c-1.106-1.106-1.106-2.9,0-4.006';
							echo '                            c1.106-1.106,2.9-1.106,4.006,0c1.35,1.35,3.145,2.093,5.054,2.093c1.909,0,3.704-0.743,5.054-2.094l7.538-7.538';
							echo '                            c2.787-2.787,2.787-7.321,0-10.108c-2.787-2.787-7.321-2.787-10.108,0l-3.227,3.227c-1.106,1.106-2.9,1.106-4.006,0';
							echo '                            c-1.106-1.106-1.106-2.9,0-4.006L34,11.877c4.996-4.996,13.124-4.995,18.12,0c4.996,4.996,4.996,13.124,0,18.12l-7.538,7.538';
							echo '                            C42.161,39.955,38.944,41.288,35.521,41.288z"></path>';
							echo '                    </g>';
							echo '                    <g>';
							echo '                        <path d="M20.94,55.869c-3.422,0-6.64-1.333-9.06-3.753c-4.996-4.996-4.996-13.124,0-18.12l7.538-7.538';
							echo '                            c4.996-4.995,13.124-4.995,18.12,0c1.106,1.106,1.106,2.9,0,4.006c-1.106,1.106-2.9,1.106-4.006,0';
							echo '                            c-2.787-2.787-7.321-2.787-10.108,0l-7.538,7.538c-2.787,2.787-2.787,7.321,0,10.108c1.35,1.35,3.145,2.094,5.054,2.094';
							echo '                            c1.909,0,3.704-0.743,5.054-2.093l3.227-3.227c1.106-1.106,2.9-1.106,4.006,0c1.106,1.106,1.106,2.9,0,4.006L30,52.117';
							echo '                            C27.58,54.536,24.363,55.869,20.94,55.869z"></path>';
							echo '                    </g>';
							echo '                </g>';
							echo '            </svg>';
							echo '        </div>';
							echo '        <div class="MuiListItemText-root MuiListItemText-multiline">';
							echo '            <span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">';
							echo                  ucwords(htmlspecialchars($title));
							echo '            </span>';
							echo '            <p class="MuiTypography-root MuiListItemText-secondary MuiTypography-body2 MuiTypography-colorTextSecondary MuiTypography-displayBlock">';
							echo '                <span title="' . htmlspecialchars($fileNumber) . '" style="white-space: break-spaces;">File Number: ' . htmlspecialchars($fileNumber) . '</span>';
							echo '            </p>';
							echo '        </div>';
							echo '    </div>';
							echo '    <div class="MuiListItemSecondaryAction-root">';
							echo '        <a href="' . $url . '" target="_blank" rel="noopener noreferrer">';
							echo '            <button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary" tabindex="0" type="button">';
							echo '                <span class="MuiButton-label">Explore</span>';
							echo '                <span class="MuiTouchRipple-root"></span>';
							echo '            </button>';
							echo '        </a>';
							echo '    </div>';
							echo '</div>';
						}
				
						echo '</div>';
					}
				}
				?>
				<div id="biorepo-collection-page-content"></div>

			  <div class="border-t-2 border-gray-200 mt-6 pt-4">
				<h2 class="text-xl mb-2">Collection Data</h2>
				<?php
				if (isset($collData['dwcaurl'])) {
					$dwcaUrl = htmlspecialchars($collData['dwcaurl']);
					$dwcaLabel = isset($LANG['DWCA_PUB']) ? $LANG['DWCA_PUB'] : 'DwC-Archive Access Point';
					$dwcFile = basename($dwcaUrl);
				
					echo '<div class="MuiListItem-container">';
					echo '    <div class="MuiListItem-root MuiListItem-gutters MuiListItem-secondaryAction">';
					echo '        <div class="MuiListItemIcon-root" style="min-width: 40px">';
					echo '            <svg class="MuiSvgIcon-root" focusable="false" viewBox="0 0 24 24" aria-hidden="true">';
					echo '                <path d="M8 16h8v2H8zm0-4h8v2H8zm6-10H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"></path>';
					echo '            </svg>';
					echo '        </div>';
					echo '        <div class="MuiListItemText-root MuiListItemText-multiline">';
					echo '            <span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">' . htmlspecialchars($dwcaLabel) . '</span>';
					echo '            <p class="MuiTypography-root MuiListItemText-secondary MuiTypography-body2 MuiTypography-colorTextSecondary MuiTypography-displayBlock">';
					echo '                <span title="file type: Darwin Core Archive (DwC-A)">Darwin Core Archive (DwC-A)</span>';					
					echo '                <span style="margin: 0px 16px;">|</span>';
					echo '                <span title="' . htmlspecialchars($dwcFile) . '" style="white-space: break-spaces;">' . htmlspecialchars($dwcFile) . '</span>';
					echo '            </p>';
					echo '        </div>';
					echo '    </div>';
					echo '    <div class="MuiListItemSecondaryAction-root">';
					echo '        <a href="' . $dwcaUrl . '" target="_blank" rel="noopener noreferrer">';
					echo '            <button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary" tabindex="0" type="button">';
					echo '                <span class="MuiButton-label">';
					echo '                    <span class="MuiButton-startIcon MuiButton-iconSizeMedium">';
					echo '                        <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" viewBox="0 0 24 24" aria-hidden="true">';
					echo '                            <path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path>';
					echo '                        </svg>';
					echo '                    </span>';
					echo '                    Download';
					echo '                </span>';
					echo '                <span class="MuiTouchRipple-root"></span>';
					echo '            </button>';
					echo '        </a>';
					echo '    </div>';
					echo '</div>';
				}
				
				// Digital Metadata (EML File)
				$emlUrl = "../datasets/emlhandler.php?collid=" . htmlspecialchars($collData['collid']);
				$emlLabel = isset($LANG['DIGITAL_METADATA']) ? $LANG['DIGITAL_METADATA'] : 'Digital Metadata';
				
				echo '<div class="MuiListItem-container">';
				echo '    <div class="MuiListItem-root MuiListItem-gutters MuiListItem-secondaryAction">';
				echo '        <div class="MuiListItemIcon-root" style="min-width: 40px">';
				echo '            <svg class="MuiSvgIcon-root" focusable="false" viewBox="0 0 64 64" aria-hidden="true">';
				echo '                <g>';
				echo '                    <g>';
				echo '                        <path d="M35.521,41.288c-3.422,0-6.64-1.333-9.06-3.753c-1.106-1.106-1.106-2.9,0-4.006';
				echo '                            c1.106-1.106,2.9-1.106,4.006,0c1.35,1.35,3.145,2.093,5.054,2.093c1.909,0,3.704-0.743,5.054-2.094l7.538-7.538';
				echo '                            c2.787-2.787,2.787-7.321,0-10.108c-2.787-2.787-7.321-2.787-10.108,0l-3.227,3.227c-1.106,1.106-2.9,1.106-4.006,0';
				echo '                            c-1.106-1.106-1.106-2.9,0-4.006L34,11.877c4.996-4.996,13.124-4.995,18.12,0c4.996,4.996,4.996,13.124,0,18.12l-7.538,7.538';
				echo '                            C42.161,39.955,38.944,41.288,35.521,41.288z"></path>';
				echo '                    </g>';
				echo '                    <g>';
				echo '                        <path d="M20.94,55.869c-3.422,0-6.64-1.333-9.06-3.753c-4.996-4.996-4.996-13.124,0-18.12l7.538-7.538';
				echo '                            c4.996-4.995,13.124-4.995,18.12,0c1.106,1.106,1.106,2.9,0,4.006c-1.106,1.106-2.9,1.106-4.006,0';
				echo '                            c-2.787-2.787-7.321-2.787-10.108,0l-7.538,7.538c-2.787,2.787-2.787,7.321,0,10.108c1.35,1.35,3.145,2.094,5.054,2.094';
				echo '                            c1.909,0,3.704-0.743,5.054-2.093l3.227-3.227c1.106-1.106,2.9-1.106,4.006,0c1.106,1.106,1.106,2.9,0,4.006L30,52.117';
				echo '                            C27.58,54.536,24.363,55.869,20.94,55.869z"></path>';
				echo '                    </g>';
				echo '                </g>';
				echo '            </svg>';
				echo '        </div>';
				echo '        <div class="MuiListItemText-root MuiListItemText-multiline">';
				echo '            <span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">' . htmlspecialchars($emlLabel) . '</span>';
				echo '            <p class="MuiTypography-root MuiListItemText-secondary MuiTypography-body2 MuiTypography-colorTextSecondary MuiTypography-displayBlock">';
				echo '                <span title="' . htmlspecialchars($fileNumber) . '" style="white-space: break-spaces;">Ecological Metadata Language (EML) File</span>';
				echo '            </p>';
				echo '        </div>';
				echo '    </div>';
				echo '    <div class="MuiListItemSecondaryAction-root">';
				echo '        <a href="' . $emlUrl . '" target="_blank" rel="noopener noreferrer">';
				echo '            <button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary">';
				echo '                <span class="MuiButton-label">View</span>';
				echo '            </button>';
				echo '        </a>';
				echo '    </div>';
				echo '</div>';
				
				// GBIF Dataset Page
				if (!empty($collData['publishtogbif']) && !empty($datasetKey)) {
					$gbifUrl = "http://www.gbif.org/dataset/" . htmlspecialchars($datasetKey);
					$gbifLabel = isset($LANG['GBIF_DATASET']) ? $LANG['GBIF_DATASET'] : 'GBIF Dataset Page';
				
					echo '<div class="MuiListItem-container">';
					echo '    <div class="MuiListItem-root MuiListItem-gutters MuiListItem-secondaryAction">';
					echo '        <div class="MuiListItemIcon-root" style="min-width: 40px">';
					echo '            <svg class="MuiSvgIcon-root" focusable="false" viewBox="0 0 64 64" aria-hidden="true">';
					echo '                <g>';
					echo '                    <g>';
					echo '                        <path d="M35.521,41.288c-3.422,0-6.64-1.333-9.06-3.753c-1.106-1.106-1.106-2.9,0-4.006';
					echo '                            c1.106-1.106,2.9-1.106,4.006,0c1.35,1.35,3.145,2.093,5.054,2.093c1.909,0,3.704-0.743,5.054-2.094l7.538-7.538';
					echo '                            c2.787-2.787,2.787-7.321,0-10.108c-2.787-2.787-7.321-2.787-10.108,0l-3.227,3.227c-1.106,1.106-2.9,1.106-4.006,0';
					echo '                            c-1.106-1.106-1.106-2.9,0-4.006L34,11.877c4.996-4.996,13.124-4.995,18.12,0c4.996,4.996,4.996,13.124,0,18.12l-7.538,7.538';
					echo '                            C42.161,39.955,38.944,41.288,35.521,41.288z"></path>';
					echo '                    </g>';
					echo '                    <g>';
					echo '                        <path d="M20.94,55.869c-3.422,0-6.64-1.333-9.06-3.753c-4.996-4.996-4.996-13.124,0-18.12l7.538-7.538';
					echo '                            c4.996-4.995,13.124-4.995,18.12,0c1.106,1.106,1.106,2.9,0,4.006c-1.106,1.106-2.9,1.106-4.006,0';
					echo '                            c-2.787-2.787-7.321-2.787-10.108,0l-7.538,7.538c-2.787,2.787-2.787,7.321,0,10.108c1.35,1.35,3.145,2.094,5.054,2.094';
					echo '                            c1.909,0,3.704-0.743,5.054-2.093l3.227-3.227c1.106-1.106,2.9-1.106,4.006,0c1.106,1.106,1.106,2.9,0,4.006L30,52.117';
					echo '                            C27.58,54.536,24.363,55.869,20.94,55.869z"></path>';
					echo '                    </g>';
					echo '                </g>';
					echo '            </svg>';
					echo '        </div>';
					echo '        <div class="MuiListItemText-root MuiListItemText-multiline">';
					echo '            <span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">' . htmlspecialchars($gbifLabel) . '</span>';
					echo '            <p class="MuiTypography-root MuiListItemText-secondary MuiTypography-body2 MuiTypography-colorTextSecondary MuiTypography-displayBlock">';
					echo '                <span title="' . htmlspecialchars($datasetKey) . '" style="white-space: break-spaces;">Dataset Key: ' . htmlspecialchars($datasetKey) . '</span>';
					echo '            </p>';
					echo '        </div>';
					echo '    </div>';
					echo '    <div class="MuiListItemSecondaryAction-root">';
					echo '        <a href="' . $gbifUrl . '" target="_blank" rel="noopener noreferrer">';
					echo '            <button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary">';
					echo '                <span class="MuiButton-label">Access</span>';
					echo '            </button>';
					echo '        </a>';
					echo '    </div>';
					echo '</div>';
				}
				
				// EDI Dataset Page
				if (!empty($collData['dynamicproperties'])) {
					$dynamicProps = json_decode($collData['dynamicproperties'], true);
					if (isset($dynamicProps['edi'])) {
						$doiNum = $dynamicProps['edi'];
						if (strpos($doiNum, 'doi:') === 0) {
							$doiNum = substr($doiNum, 4);
						}
						$ediUrl = "https://www.doi.org/" . htmlspecialchars($doiNum);
						echo '<div class="MuiListItem-container">';
						echo '    <div class="MuiListItem-root MuiListItem-gutters MuiListItem-secondaryAction">';
						echo '        <div class="MuiListItemIcon-root" style="min-width: 40px">';
						echo '            <svg class="MuiSvgIcon-root" focusable="false" viewBox="0 0 64 64" aria-hidden="true">';
						echo '                <g>';
						echo '                    <g>';
						echo '                        <path d="M35.521,41.288c-3.422,0-6.64-1.333-9.06-3.753c-1.106-1.106-1.106-2.9,0-4.006';
						echo '                            c1.106-1.106,2.9-1.106,4.006,0c1.35,1.35,3.145,2.093,5.054,2.093c1.909,0,3.704-0.743,5.054-2.094l7.538-7.538';
						echo '                            c2.787-2.787,2.787-7.321,0-10.108c-2.787-2.787-7.321-2.787-10.108,0l-3.227,3.227c-1.106,1.106-2.9,1.106-4.006,0';
						echo '                            c-1.106-1.106-1.106-2.9,0-4.006L34,11.877c4.996-4.996,13.124-4.995,18.12,0c4.996,4.996,4.996,13.124,0,18.12l-7.538,7.538';
						echo '                            C42.161,39.955,38.944,41.288,35.521,41.288z"></path>';
						echo '                    </g>';
						echo '                    <g>';
						echo '                        <path d="M20.94,55.869c-3.422,0-6.64-1.333-9.06-3.753c-4.996-4.996-4.996-13.124,0-18.12l7.538-7.538';
						echo '                            c4.996-4.995,13.124-4.995,18.12,0c1.106,1.106,1.106,2.9,0,4.006c-1.106,1.106-2.9,1.106-4.006,0';
						echo '                            c-2.787-2.787-7.321-2.787-10.108,0l-7.538,7.538c-2.787,2.787-2.787,7.321,0,10.108c1.35,1.35,3.145,2.094,5.054,2.094';
						echo '                            c1.909,0,3.704-0.743,5.054-2.093l3.227-3.227c1.106-1.106,2.9-1.106,4.006,0c1.106,1.106,1.106,2.9,0,4.006L30,52.117';
						echo '                            C27.58,54.536,24.363,55.869,20.94,55.869z"></path>';
						echo '                    </g>';
						echo '                </g>';
						echo '            </svg>';
						echo '        </div>';
						echo '        <div class="MuiListItemText-root MuiListItemText-multiline">';
						echo '            <span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">EDI Dataset Page</span>';
						echo '            <p class="MuiTypography-root MuiListItemText-secondary MuiTypography-body2 MuiTypography-colorTextSecondary MuiTypography-displayBlock">';
						echo '                <span title="' . htmlspecialchars($doiNum) . '" style="white-space: break-spaces;">DOI: ' . htmlspecialchars($doiNum) . '</span>';
						echo '            </p>';
						echo '        </div>';
						echo '    </div>';
						echo '    <div class="MuiListItemSecondaryAction-root">';
						echo '        <a href="' . $ediUrl . '" target="_blank" rel="noopener noreferrer">';
						echo '            <button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary">';
						echo '                <span class="MuiButton-label">Access</span>';
						echo '            </button>';
						echo '        </a>';
						echo '    </div>';
						echo '</div>';
					}
				}
				
				// iDigBio Dataset Page
				if (!empty($collData['publishtoidigbio'])) {
					$idigbioKey = $collManager->getIdigbioKey();
					if (!$idigbioKey) {
						$idigbioKey = $collManager->findIdigbioKey($collData['recordid']);
					}
					if ($idigbioKey) {
						$idigbioUrl = "https://www.idigbio.org/portal/recordsets/" . htmlspecialchars($idigbioKey);
						$idigbioLabel = isset($LANG['IDIGBIO_DATASET']) ? $LANG['IDIGBIO_DATASET'] : 'iDigBio Dataset Page';
				
						echo '<div class="MuiListItem-container">';
						echo '    <div class="MuiListItem-root MuiListItem-gutters MuiListItem-secondaryAction">';
						echo '        <div class="MuiListItemIcon-root" style="min-width: 40px">';
						echo '            <svg class="MuiSvgIcon-root" focusable="false" viewBox="0 0 64 64" aria-hidden="true">';
						echo '                <g>';
						echo '                    <g>';
						echo '                        <path d="M35.521,41.288c-3.422,0-6.64-1.333-9.06-3.753c-1.106-1.106-1.106-2.9,0-4.006';
						echo '                            c1.106-1.106,2.9-1.106,4.006,0c1.35,1.35,3.145,2.093,5.054,2.093c1.909,0,3.704-0.743,5.054-2.094l7.538-7.538';
						echo '                            c2.787-2.787,2.787-7.321,0-10.108c-2.787-2.787-7.321-2.787-10.108,0l-3.227,3.227c-1.106,1.106-2.9,1.106-4.006,0';
						echo '                            c-1.106-1.106-1.106-2.9,0-4.006L34,11.877c4.996-4.996,13.124-4.995,18.12,0c4.996,4.996,4.996,13.124,0,18.12l-7.538,7.538';
						echo '                            C42.161,39.955,38.944,41.288,35.521,41.288z"></path>';
						echo '                    </g>';
						echo '                    <g>';
						echo '                        <path d="M20.94,55.869c-3.422,0-6.64-1.333-9.06-3.753c-4.996-4.996-4.996-13.124,0-18.12l7.538-7.538';
						echo '                            c4.996-4.995,13.124-4.995,18.12,0c1.106,1.106,1.106,2.9,0,4.006c-1.106,1.106-2.9,1.106-4.006,0';
						echo '                            c-2.787-2.787-7.321-2.787-10.108,0l-7.538,7.538c-2.787,2.787-2.787,7.321,0,10.108c1.35,1.35,3.145,2.094,5.054,2.094';
						echo '                            c1.909,0,3.704-0.743,5.054-2.093l3.227-3.227c1.106-1.106,2.9-1.106,4.006,0c1.106,1.106,1.106,2.9,0,4.006L30,52.117';
						echo '                            C27.58,54.536,24.363,55.869,20.94,55.869z"></path>';
						echo '                    </g>';
						echo '                </g>';
						echo '            </svg>';
						echo '        </div>';
						echo '        <div class="MuiListItemText-root MuiListItemText-multiline">';
						echo '            <span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">' . htmlspecialchars($idigbioLabel) . '</span>';
						//echo '            <p class="MuiTypography-root MuiListItemText-secondary MuiTypography-body2 MuiTypography-colorTextSecondary MuiTypography-displayBlock">';
						//echo '                <span title="' . htmlspecialchars($fileNumber) . '" style="white-space: break-spaces;">' . htmlspecialchars($fileNumber) . '</span>';
						//echo '            </p>';
						echo '        </div>';
						echo '    </div>';
						echo '    <div class="MuiListItemSecondaryAction-root">';
						echo '        <a href="' . $idigbioUrl . '" target="_blank" rel="noopener noreferrer">';
						echo '            <button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary">';
						echo '                <span class="MuiButton-label">Open</span>';
						echo '            </button>';
						echo '        </a>';
						echo '    </div>';
						echo '</div>';
					}
				}
				?>

			  </div>
			</div>		
			
			<fieldset style='padding:10px;width:300px;background-color:#FFFFCC;'>
				<legend><b><?php echo (isset($LANG['EXTRA_STATS']) ? $LANG['EXTRA_STATS'] : 'Extra Statistics'); ?></b></legend>
				<div style="margin:3px;">
					<a href="collprofiles.php?collid=<?php echo $collid; ?>&stat=geography#geographystats"><?php echo (isset($LANG['SHOW_GEOG_DIST']) ? $LANG['SHOW_GEOG_DIST'] : 'Show Geographic Distribution'); ?></a>
				</div>
				<div style="margin:3px;">
					<a href="collprofiles.php?collid=<?php echo $collid; ?>&stat=taxonomy#taxonomystats"><?php echo (isset($LANG['SHOW_FAMILY_DIST']) ? $LANG['SHOW_FAMILY_DIST'] : 'Show Family Distribution'); ?></a>
				</div>
			</fieldset>
		<?php
			include('collprofilestats.php');
		} elseif ($collData) {
		?>
			<h2><?php echo $DEFAULT_TITLE . ' ' . (isset($LANG['COLLECTION_PROJECTS']) ? $LANG['COLLECTION_PROJECTS'] : 'Natural History Collections and Observation Projects'); ?></h2>
			<div style='margin:10px;clear:both;'>
				<?php
				echo (isset($LANG['RSS_FEED']) ? $LANG['RSS_FEED'] : 'RSS feed') . ': <a href="../datasets/rsshandler.php" target="_blank">' . $collManager->getDomain() . $CLIENT_ROOT . 'collections/datasets/rsshandler.php</a>';
				?>
				<hr />
			</div>
			<table style='margin:10px;'>
				<?php
				foreach ($collData as $cid => $collArr) {
				?>
					<tr>
						<td style='text-align:center;vertical-align:top;'>
							<?php
							$iconStr = $collArr['icon'];
							if ($iconStr) {
								if (substr($iconStr, 0, 6) == 'images') $iconStr = '../../' . $iconStr;
							?>
								<img src='<?php echo $iconStr; ?>' style='border-size:1px;height:30;width:30;' /><br />
							<?php
								echo $collArr['institutioncode'];
								if ($collArr['collectioncode']) echo '-' . $collArr['collectioncode'];
							}
							?>
						</td>
						<td>
							<h3>
								<a href='collprofiles.php?collid=<?php echo $cid; ?>'>
									<?php echo $collArr['collectionname']; ?>
								</a>
							</h3>
							<div style='margin:10px;'>
								<?php
								$collManager->setCollid($cid);
								echo $collManager->getMetadataHtml($LANG, $LANG_TAG);
								?>
							</div>
							<div style='margin:5px 0px 15px 10px;'>
								<a href='collprofiles.php?collid=<?php echo $cid; ?>'><?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More Information'); ?></a>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan='2'>
							<hr />
						</td>
					</tr>
				<?php
				}
				?>
			</table>
		<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>

</html>