<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceDataset.php');
include_once($SERVER_ROOT . '/classes/utilities/Language.php');
include_once($SERVER_ROOT . '/classes/utilities/Sanitize.php');

Language::load('collections/datasets/public');

header("Content-Type: text/html; charset=" . $CHARSET);

// Datasets
$datasetid = array_key_exists('datasetid', $_REQUEST) ? Sanitize::int($_REQUEST['datasetid']) : 0;

$datasetManager = new OccurrenceDataset();
$dArr = $datasetManager->getPublicDatasetMetadata($datasetid);

# NEON edit
$datasetManager = new OccurrenceDataset();

$mdArr = $datasetManager->getDatasetMetadata($datasetid);

$isEditor = 0;
if ($SYMB_UID == $mdArr['uid']) {
	$isEditor = 1;
} elseif (isset($mdArr['roles'])) {
	if (in_array('DatasetAdmin', $mdArr['roles'])) {
		$isEditor = 1;
	} elseif (in_array('DatasetEditor', $mdArr['roles'])) {
		$isEditor = 1;
	}
} elseif ($IS_ADMIN) {
	$isEditor = 1;
}
# end NEON edit
?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">
	<head>
		<title><?= $LANG['DATASET'] ?>: <?= $dArr['name'] ?></title>
		<?php
		include_once($SERVER_ROOT . '/includes/head.php');
		?>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT . '/includes/header.php');
		?>
		<div class="navpath">
			<a href="<?= htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php"><?= $LANG['HOME']; ?></a> &gt;&gt;
			<b><?= $LANG['DATASET']; ?>: <?= $dArr['name'] ;?></b>
		</div>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<!-- NEON edit -->
			<?php
			if ($isEditor == 1) {
				echo "<a href='../../neon/datasets/neondatasetmanager.php?datasetid=" . $datasetid . "'>Manage Dataset</a>";
			}
			?>
			<!-- end NEON edit -->
			<h1 class="page-heading"><?= $LANG['DATASET']; ?>: <?= $dArr['name'] ;?></h1>
			<div><?= $dArr['description'] ;?></div>
			<?php
			if ($dArr['bibliographicCitation']) {
				?>
				<h2> Citation </h2>
				<div><?= $dArr['bibliographicCitation'] ?></div>
				<?php
			};
			$searchUrl = '../../collections/list.php?datasetid=' . $datasetid;
			$tableUrl = '../../collections/listtabledisplay.php?datasetid=' . $datasetid;
			$taxaUrl = '../../collections/list.php?datasetid=' . $datasetid . '&tabindex=0';
			$ocArr = $datasetManager->getOccurrences($datasetid);
			?>
			<h2><?= $LANG['INCLUDES'] ?> <?= count($ocArr) ?> <?= $LANG['RECORDS'] ?></h2>

			<p><a class="btn" href="<?= $searchUrl ?>"><?= $LANG['VIEW_AND_DOWNLOAD'] ?></a></p>
			<p><a class="btn" href="<?= $tableUrl ?>"><?= $LANG['VIEW_SAMPLE'] ?></a></p>
			<p><a class="btn" href="<?= $taxaUrl ?>"><?= $LANG['VIEW_LIST'] ?></a></p>

			<!-- NEON addition: Download pub ready table -->
			<?php
			if($datasetid){
				?>
				<form action="<?= $CLIENT_ROOT ?>/neon/requests/exporthandler.php" method="post" style="margin-top:15px;">
					<input type="hidden" name="pubID" value="<?= $datasetid; ?>" />
					<input type="hidden" name="type" value="dataset" />
					<input type="hidden" name="exportTask" value="pubtable" />

					<button type="submit" class="btn">
						Download Publication-Ready Table
					</button>
				</form>
				<?php
			}
			?>
			<!-- End NEON addition -->

		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
