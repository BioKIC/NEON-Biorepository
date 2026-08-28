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
	<title><?php echo $DEFAULT_TITLE . ' ' . ($collid && isset($collData[$collid]) ? $collData[$collid]['collectionname'] : ''); ?></title>
	<meta name="keywords" content="Natural history collections,<?php echo ($collid ? $collData[$collid]['collectionname'] : ''); ?>" />
	<meta http-equiv="Cache-control" content="no-cache, no-store, must-revalidate">
	<meta http-equiv="Pragma" content="no-cache">
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
</head>

<style type="text/css">
	.MuiAccordion-root.Mui-expanded:last-child {
		margin-bottom: 20 !important;
		}
	.MuiAccordion-root.Mui-expanded:first-child {
		margin-top: 20 !important;
		}
	.MuiAccordion-root.Mui-expanded {
		margin: 16px 0 !important;
	}
	.MuiTab-wrapper {
		font-size: 0.825rem;
	}
</style>


<body>
	<div id="innertext">
		<div id="biorepo-collections-content"></div>
	</div>
</body>

</html>