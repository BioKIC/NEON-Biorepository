<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$quarter = $_POST['quarter'] ?? $_GET['quarter'] ?? null;


if (!$quarter) {
    die('No report quarter selected.');
}

$reports = new NEONReports();
$reportsArr = $reports->getQuarterlyReport($quarter);
$reportDate = $reports->getReportDate($quarter,'quarterly');
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

elseif(array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> NEON Quarterly Sample Use Report </title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<?php
		$activateJQuery = true;
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
    <link rel="stylesheet" href="../css/tables.css">
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="../../../index.php">Home</a> &gt;&gt;
			<a href="../index.php">Management Tools</a> &gt;&gt;
			<b>NEON Quarterly Sample Use Report</b>
		</div>
		<div id="innertext">
<?php
if ($isEditor) {
?>
	<h1>NEON Quarterly Sample Use Report: <?php echo htmlspecialchars($quarter); ?></h1>
 <?php

	if ($reportDate) {
		echo '<p><strong>Report generated: </strong> ' . htmlspecialchars($reportDate) . '</p>';
	}
	if (!empty($reportsArr)) {

		$tables = [];

		foreach ($reportsArr as $row) {
			$period    = $row['period'];
			$tabletype = $row['tabletype'];

			$tables[$period][$tabletype][] = $row;
		}

		$excludeTableTypes = [
				'Samples Use Bar Chart'
			];


		foreach ($tables as $period => $tableTypes) {

			foreach ($tableTypes as $tableType => $rows) {

				if (in_array($tableType, $excludeTableTypes, true)) {
					continue;
				}

				foreach ($rows as &$r) {
					unset($r['pk'], $r['name'], $r['period'], $r['tabletype'], $r['date']);
				}
				unset($r);

				$rows =  $reports->removeNullColumns($rows);

				if (empty($rows)) continue;

				echo '<h2>' . htmlspecialchars($period) . ': ' . htmlspecialchars($tableType) . '</h2>';
				$headers = array_map(
					fn($h) => ucwords(str_replace('_', ' ', $h)),
					array_keys($rows[0])
				);

				if ($tableType == 'Researchers and Requests by Status') {
					echo 'The number of requests are those newly reaching a maximum status during the indicated period. Completed 
					requests are only included within the active request category if they were also 
					newly active within the period. Researchers includes all researchers associated with those requests. 
					Researchers may be repeated across different statuses.';
				}
				elseif ($tableType == 'Researchers and Samples by Collection'){
					echo 'The number of researchers is the total number of researchers involved in any requests that are newly active
					 or pending within the period and associated with the collection.  Researchers may be repeated across 
					 collections but are unique within a collection. "Samples" indicate the number of samples associated with the 
					 included requests. "PhysicalSamples" removes samples for which only images or Biorepository-collected data are explicitly involved in research, 
					 excluding requests entirely for outreach or internal purposes. In either case, samples" may be repeated if they are involved in multiple requests. 
					 Samples values of zero indicate that the collection is involved only in pending requests for which samples have not yet been identified';

				}
				if ($tableType == 'Samples by Primary Research Field') {
					echo 'Sample numbers are calculated as in the Researchers and Samples by Collection table';
				}

				echo $utilities->htmlTable(array_map('array_values', $rows),$headers);
				echo '
					<form method="post" action="exportquarterlyreporthandler.php" style="margin-bottom:20px;">
						<input type="hidden" name="quarter" value="' . htmlspecialchars($quarter, ENT_QUOTES) . '">
						<input type="hidden" name="period" value="' . htmlspecialchars($period, ENT_QUOTES) . '">
						<input type="hidden" name="tabletype" value="' . htmlspecialchars($tableType, ENT_QUOTES) . '">
						<button type="submit">Download table above as CSV</button>
					</form>';

			}
		}
	}
?>
	<h2>Samples Distributed, Consumed, and Generated</h2>

	<form method="post" action="exportquarterlydataset.php">
		<input type="hidden" name="type" value="samples_distributed">
		<input type="hidden" name="quarter" value="<?= htmlspecialchars($quarter, ENT_QUOTES) ?>">
		<button type="submit">Download Samples Distributed</button>
	</form>

	<form method="post" action="exportquarterlydataset.php">
		<input type="hidden" name="type" value="samples_consumed">
		<input type="hidden" name="quarter" value="<?= htmlspecialchars($quarter, ENT_QUOTES) ?>">
		<button type="submit">Download Samples Consumed</button>
	</form>

	<form method="post" action="exportquarterlydataset.php">
		<input type="hidden" name="type" value="samples_generated">
		<input type="hidden" name="quarter" value="<?= htmlspecialchars($quarter, ENT_QUOTES) ?>">
		<button type="submit">Download Samples Generated</button>
	</form>

	<h2>Data Updates From Sample Use</h2>

	<form method="post" action="exportquarterlydataset.php">
		<input type="hidden" name="type" value="data_edits">
		<input type="hidden" name="quarter" value="<?= htmlspecialchars($quarter, ENT_QUOTES) ?>">
		<button type="submit">Download Data Edits</button>
	</form>

	<form method="post" action="exportquarterlydataset.php">
		<input type="hidden" name="type" value="datasets_generated">
		<input type="hidden" name="quarter" value="<?= htmlspecialchars($quarter, ENT_QUOTES) ?>">
		<button type="submit">Download Datasets Generated</button>
	</form>
<?php

} 
else {
	echo '<h3>Please login to get access to this page.</h3>';
}
?>
	</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
  </body>
  <script src="../js/sortables.js"></script>

<script>
	(function () {

		const rawData = chartData_<?= md5($reportDate) ?>;

		const labels = rawData.map(r => r.awardYear);
		const rawCounts = rawData.map(r => r.count);

		const prefixes = [...new Set(
			rawData.map(r => r.awardYear.substring(0, 3))
		)];

		const colorPalette = [
			'#4e79a7', // blue
			'#1f77b4', // dark blue
			'#7f7f7f', // neutral gray
			'#393b79', // indigo
			'#3182bd', // steel blue
			'#e6550d'  // burnt orange
		];


		const datasets = prefixes.map((prefix, i) => ({
			label: prefix,
			backgroundColor: colorPalette[i % colorPalette.length],
			data: rawData.map((r, idx) =>
				r.awardYear.startsWith(prefix)
					? rawCounts[idx]
					: null
			),
			categoryPercentage: 3.0,
			barPercentage: 3.0
		}));


		const ctx = document
			.getElementById('samplesByClassChart')
			.getContext('2d');

		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: datasets
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,

				scales: {
					x: {
						title: {
							display: true,
							text: 'Award Year of Initiation'
						}					},
					y: {
						beginAtZero: true,
						title: {
							display: true,
							text: 'Number of Inquiries'
						}
					}
				},

				plugins: {
					legend: {
						display: true,
						position: 'bottom'
					},
					tooltip: {
						callbacks: {
							title: function (tooltipItems) {
								return labels[tooltipItems[0].dataIndex];
							},
							label: function (tooltipItem) {
								const index = tooltipItem.dataIndex;
								return 'Samples: ' + rawCounts[index].toLocaleString();
							}
						}
					}
				}
			}
		});

	})();
	</script>


</html>