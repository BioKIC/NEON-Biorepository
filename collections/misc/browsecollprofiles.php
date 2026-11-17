<?php
include_once('../../config/symbini.php');
require_once $SERVER_ROOT . '/neon/classes/SampleTypeUrls.php';
$sampleTypeProfileUrls = array();
try {
    $sampleTypeProfileUrls = SampleTypeUrls::shared()->allUrls($CLIENT_ROOT);
} catch (Throwable $error) {
    error_log('Sample explorer profile URLs: ' . $error->getMessage());
}
header('Content-Type: text/html; charset=' . $CHARSET);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<title>NEON Biorepository | Explore Sample Types</title>
	<meta http-equiv="Cache-control" content="no-cache, no-store, must-revalidate">
	<meta http-equiv="Pragma" content="no-cache">
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
	<script>window.NEON_SAMPLE_TYPE_URLS = <?= json_encode((object) $sampleTypeProfileUrls, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
	<!-- NEON: confirm before listing every sample. -->
	<link rel="stylesheet" href="<?= $CLIENT_ROOT ?>/neon/css/all-samples-confirmation.css?v=<?= filemtime($SERVER_ROOT . '/neon/css/all-samples-confirmation.css') ?>">
	<script defer src="<?= $CLIENT_ROOT ?>/neon/js/all-samples-confirmation.js?v=<?= filemtime($SERVER_ROOT . '/neon/js/all-samples-confirmation.js') ?>" data-count-url="<?= $CLIENT_ROOT ?>/neon/search/rpc/all-samples-count.php"></script>
</head>

<body>
	<div id="innertext">
		<div id="biorepo-collections-content"></div>
		<noscript>Enable JavaScript to search and filter sample types.</noscript>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>

</html>

