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
<html>

<head>
	<title>Browse NEON Sample Types</title>
	<meta name="keywords" content="Natural history collections,<?php echo ($collid ? $collData[$collid]['collectionname'] : ''); ?>" />
	<meta http-equiv="Cache-control" content="no-cache, no-store, must-revalidate">
	<meta http-equiv="Pragma" content="no-cache">
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
</head>

<body>
	<div id="innertext">
		<section>
			<div style="border-bottom-width:2px;border-color:#0472cf;border-left-width:20px;border-right-width:2px;border-style:solid;border-top-width:2px;padding:10px;">
				<p>Our downloadable table contains a wealth of information about all NEON sample types, including associated protocols and data products, links to sample type descriptions, summary statistics, and more:</p>
				<span>
					<form class="button-form" action="../download/downloadsampletypes.php" method="post">
						<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary" type="submit">
						<span class="MuiButton-label" style="font-size: 0.55rem;">
							<i class="fa-solid fa-download" style="font-size: 0.75rem; margin-right: 1.2em;"></i>
								Download Sample Type Summary Table (CSV)
							<span class="MuiButton-endIcon MuiButton-iconSizeMedium">
								<i class="fa-solid fa-chevron-right" style="font-size: 0.55rem; margin-left: 1.4em;"></i>
							</span>
						</span>
						<span class="MuiTouchRipple-root"></span>
							</button>
							<input name="searchvar" type="hidden" value="<?= $searchVar ?>" />
							<input name="dltype" type="hidden" value="specimen" />
					</form>
				</span>
			</div>
		</section>
		<br>
		<div id="biorepo-collections-content"></div>
	</div>
</body>

</html>