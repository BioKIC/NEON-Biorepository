<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
include_once('config/symbini.php');
include_once('content/lang/index.' . $LANG_TAG . '.php');
include_once($SERVER_ROOT . '/neon/classes/PortalStatistics.php');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
header("Content-Type: text/html; charset=" . $CHARSET);

$stats = new PortalStatistics();

$statsArr = json_encode($stats->getBlueNeonStats());
?>
<html>

<head>
	<title><?php echo $DEFAULT_TITLE; ?> Home</title>
	<meta http-equiv="Expires" content="Tue, 01 Jan 1995 12:12:12 GMT">
	<meta http-equiv="Pragma" content="no-cache">
	<script src="js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="js/jquery-ui.min.js" type="text/javascript"></script>
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	include_once($SERVER_ROOT . '/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT . '/neon/js/d3.min.js'; ?>" type="text/javascript"></script>
</head>

<script type="module">
	// countup animation
	import { CountUp } from './neon/js/countUp.min.js';
	
	window.onload = function() {
		var data = <?php echo $statsArr; ?>;
	
		// Array of target elements
		var targets = [
		  { id: 'speciesCount', value: data.noSpecies, suffix: ' species' },
		  { id: 'recordCount', value: data.noRecords, suffix: ' samples' },
		  { id: 'imageCount', value: data.noImages, suffix: ' images' },
		  { id: 'yearCount', value: data.noYears, suffix: ' years' },
		  { id: 'sampleTypeCount', value: data.noSampleTypes, suffix: ' sample types' },
		  { id: 'siteCount', value: data.noSites, suffix: ' sites' },
		];
	
		// Iterate over the targets array and initialize CountUp for each element
		targets.forEach(function(target) {
		  var countUp = new CountUp(target.id, target.value, { enableScrollSpy: true, suffix: target.suffix, duration: 3 });
		  countUp.start();
		});
	};
</script>

<body class="home-page">
	<div id="biorepo-page"></div>
	<!-- This is inner text! -->
	<div id="innertext">
		<div id="biorepo-home-page-content"></div>
	</div>
</body>
</html>
