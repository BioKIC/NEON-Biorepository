<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/datasets/public.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/datasets/public.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/datasets/public.en.php');

header("Content-Type: text/html; charset=".$CHARSET);

// Datasets
$datasetid = array_key_exists('datasetid', $_REQUEST) ? $_REQUEST['datasetid'] : 0;

if (!is_numeric($datasetid)) $datasetid = 0;

$datasetManager = new OccurrenceDataset();
$dArr = $datasetManager->getPublicDatasetMetadata($datasetid);
$rArr = $datasetManager->getRequestInquiryMetadata($datasetid);
$searchUrl = '../../collections/list.php?datasetid=' . $datasetid;
$tableUrl = '../../collections/listtabledisplay.php?datasetid=' . $datasetid;
$taxaUrl = '../../collections/list.php?datasetid=' . $datasetid . '&tabindex=0';
// $downloadUrl = '../../collections/download/index.php?datasetid='.$datasetid;


$datasetManager = new OccurrenceDataset();

$mdArr = $datasetManager->getDatasetMetadata($datasetid);

// Dataset access levels:
// 1 = Full Access: NEON Biorepository staff/editors who can manage all project information,
//     samples, and user access.
// 2 = Read/Write: Project PIs who can edit project information and samples and view user access,
//     but cannot manage users.
// 3 = Read Only: Users who need access to view project information, citations, and sample data,
//     but cannot make changes or view user access.

$isEditor = 0;
if (!empty($mdArr)) {
	if ($SYMB_UID == $mdArr['uid']) {
		$isEditor = 1;
	} elseif (isset($mdArr['roles'])) {
		if (in_array('DatasetAdmin', $mdArr['roles'])) {
			$isEditor = 1;
		} elseif (in_array('DatasetEditor', $mdArr['roles'])) {
			$isEditor = 2;
			$role = $LANG['EDITOR'];
		} elseif (in_array('DatasetReader', $mdArr['roles'])) {
			$isEditor = 3;
		}
	} elseif ($IS_ADMIN) {
		$isEditor = 1;
	}
}

$ocArr = $datasetManager->getOccurrences($datasetid);
?>
<style>
	.request-meta {
		display: flex;
		justify-content: space-between;
		gap: 60px;
		margin: 30px 0;
	}
	
	.request-meta-main {
		flex: 1;
	}
	
	.request-meta-sidebar {
		width: 35%;
		padding-left: 30px;
	}
	
	.request-field {
		margin-bottom: 24px;
		font-weight: 400;
	}
	
	.request-label {
		font-weight: bold;
		margin-bottom: 5px;
	}
	
	.request-value {
		font-size: 16px;
		line-height: 1.5;
	}
	
	@media (max-width: 768px) {
		.request-meta {
			display: block;
		}
	
		.request-meta-sidebar {
			width: auto;
			border-left: 0;
			padding-left: 0;
			margin-top: 30px;
		}
	}
	.dataset-management-button {
		display: inline-flex;
		align-items: center;
		gap: 14px;
		background-color: #0073CF;
		border: 0;
		border-radius: 2px;
		color: #fff;
		font-size: 13px;
		font-weight: bold;
		letter-spacing: 0.12em;
		text-transform: uppercase;
		padding: 10px 20px;
		cursor: pointer;
		transition: background-color 0.2s ease;
		font-family: Inter, Helvetica, Arial, sans-serif; 
	}
	
	.dataset-management-button .button-arrow {
		display: inline-block;
		font-size: 26px;
		font-weight: bold;
		line-height: 1;
	}
	
	.dataset-management-button:hover {
		background-color: #0095D9;
	}
	
	.dataset-management-button:disabled {
		background-color: #d5d7d7;
		color: #a7aaaa;
		cursor: default;
	}
	
	.dataset-management-button:disabled:hover {
		background-color: #d5d7d7;
		color: #a7aaaa;
	}

	.view-samples-button {
		display: inline-flex;
		align-items: center;
		gap: 14px;
		background-color: #fff;
		border: 2px solid #0073CF;
		border-radius: 3px;
		color: #0073CF;
		font-size: 13px;
		font-weight: bold;
		letter-spacing: 0.12em;
		text-transform: uppercase;
		text-decoration: none;
		padding: 10px 20px;
		cursor: pointer;
		font-family: Inter, Helvetica, Arial, sans-serif;
	}
	
	.view-samples-button .button-arrow {
		display: inline-block;
		font-size: 26px;
		font-weight: bold;
		line-height: 1;
	}
	
	.view-samples-button:hover {
		color: #0095D9;
		border-color: #0095D9;
	}
	
	.view-samples-button:hover .button-arrow {
		transform: translateX(3px);
	}

	.view-samples-container {
		padding: 20px;
	}
	
	.sample-types-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 15px;
	}
	
	.sample-types-header h2 {
		margin: 0;
	}
	
	.view-samples-button:hover {
		text-decoration: none;
		color: #0095D9;
	}
	
	.view-samples-button:hover .button-text {
		text-decoration: underline;
	}
	
	.view-samples-button .button-arrow {
		text-decoration: none;
	}
</style>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo strip_tags($dArr['name']); ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	<body>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<p style="color:#0073CF; font-size:13px; font-weight:bold; letter-spacing:0.2em; text-transform:uppercase; margin:0;">NEON Biorepository Research Project</p>
				<button type="button" class="Mui dataset-management-button" <?php echo ($isEditor) ? 'onclick="window.location.href=\'neondatasetmanager.php?datasetid=' . $datasetid . '\'"' : 'disabled'; ?>>
					Manage Project <span class="button-arrow">›</span>
				</button>
			</div>
			<h1 style="font-weight:300; font-size:2.8rem; margin-top: 27px;"><?php echo $dArr['name']; ?></h1>
			<p style="color:#000000;"><?php echo $dArr['description'] ;?></p>
			<div class="request-meta">
				<div class="request-meta-main">
					<div class="request-field">
						<div class="request-label">PI Name</div>
						<div class="request-value"><?php echo $rArr['name']; ?></div>
					</div>
			
					<div class="request-field">
						<div class="request-label">PI Affiliation</div>
						<div class="request-value"><?php echo $rArr['institution']; ?></div>
					</div>

				</div>
			
				<div class="request-meta-sidebar">
					<div class="request-field">
						<div class="request-label">Start Date</div>
						<div class="request-value"><?php echo $rArr['activeDate']; ?></div>
					</div>
			
					<div class="request-field">
						<div class="request-label">End Date</div>
						<div class="request-value"><?php echo $rArr['completeDate']; ?></div>
					</div>

				</div>
			</div>

			<!--List Sample Types in React Table-->
			<div class="sample-types-header">
				<h2>Sample Types Used</h2>
			
				<a class="Mui view-samples-button" href="<?php echo htmlspecialchars($searchUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
					<span class="button-text">Explore Samples</span>
					<span class="button-arrow">›</span>
				</a>
			</div>
			<script>
				window.tableData = <?php echo json_encode(
					$dArr['sampleTypes'] ?? array(),
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
				); ?>;
			</script>
			<div id="neon-table"></div>
			<!--Site Map with Sample Sites-->
			<h2>Field Sites Used</h2>
			<script>
				window.sampleSites = <?php echo json_encode(
					$dArr['sites'] ?? [],
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
				); ?>;
			</script>
			<div id="sample-site-map"></div>
		</div>
	</body>
</html>
