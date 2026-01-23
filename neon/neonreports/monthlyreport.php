<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$month = $_POST['month'] ?? $_GET['month'] ?? null;

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

if (!$month) {
    die('No report month selected.');
}

$reports = new NEONReports();
$reportsArr = $reports->getMonthlyReport($month);
$reportDate = $reports->getReportDate($month,'monthly');
$headerArr = ['Statistic', 'Current','Change'];
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

elseif(array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> NEON Monthly Report </title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<?php
		$activateJQuery = true;
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
    <link rel="stylesheet" href="../css/tables.css">
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="../../../index.php">Home</a> &gt;&gt;
			<a href="../index.php">Management Tools</a> &gt;&gt;
			<b>NEON Monthly Report</b>
		</div>
		<div id="innertext">
<?php
if ($isEditor) {
?>
	<h1>NEON Monthly Report: <?php echo htmlspecialchars($month); ?></h1>
 <?php

	if ($reportDate) {
		echo '<p><strong>Report generated: </strong> ' . htmlspecialchars($reportDate) . '</p>';
	}
	if (!empty($reportsArr)) {

		$general = [];
		$request = [];
		$sample  = [];

		foreach ($reportsArr as $row) {
			$type = array_shift($row); 

			switch ($type) {
				case 'general':
					$general[] = $row;
					break;
				case 'request':
					$request[] = $row;
					break;
				case 'sample':
					$sample[] = $row;
					break;
			}
		}

		if ($general) {
			echo '<h2>General Statistics</h2>';
			echo $utilities->htmlTable($general, $headerArr);
		}

		?>
		<form method="post" action="exportmonthlyreporthandler.php">
			<input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="general">
			<button type="submit">Export General Statistics Report</button>
		</form>
		<?php

		if ($request) {
			echo '<h2>Request Summary</h2>';
			echo $utilities->htmlTable($request, $headerArr);
		}

		?>
		<form method="post" action="exportmonthlyreporthandler.php">
			<input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="request">
			<button type="submit">Export Request Summary</button>
		</form>
		<?php

		if ($sample) {
						
			?>
			<h2>Samples Received by Class To Date</h2>

			<div style="width:100%; max-width:1000px;">
				<canvas id="samplesByClassChart"></canvas>
			</div>
			<?php

			echo $utilities->htmlTable($sample, $headerArr);
		}
		?>
		<form method="post" action="exportmonthlyreporthandler.php">
			<input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="sample">
			<button type="submit">Export Samples Received</button>
		</form>
		<?php


	}
	$chartData = $reports->samplesReceivedBarChart($reportDate);
?>
	<script>
	const chartData_<?= md5($reportDate) ?> = <?= json_encode($chartData) ?>;
	</script>
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

		const labels = rawData.map(r => r.sampleClass);
		const rawCounts = rawData.map(r => r.count);
		const logCounts = rawCounts.map(v => Math.log10(v));

		const prefixes = [...new Set(
			rawData.map(r => r.sampleClass.substring(0, 3))
		)];

		const colorPalette = [
			'#4e79a7', // blue
			'#f28e2b', // orange
			'#e15759', // red
			'#76b7b2', // teal
			'#59a14f', // green
			'#edc949', // yellow
			'#af7aa1', // purple
			'#ff9da7', // pink
			'#9c755f', // brown
			'#bab0ab', // gray
			'#1f77b4', // dark blue
			'#ff7f0e', // dark orange
			'#2ca02c', // dark green
			'#d62728', // dark red
			'#9467bd', // violet
			'#8c564b', // coffee
			'#e377c2', // magenta
			'#7f7f7f', // neutral gray
			'#bcbd22', // olive
			'#17becf', // cyan
			'#393b79', // indigo
			'#637939', // moss
			'#8c6d31', // gold-brown
			'#843c39', // brick
			'#7b4173', // plum
			'#3182bd', // steel blue
			'#31a354', // emerald
			'#756bb1', // lavender
			'#636363', // charcoal
			'#e6550d'  // burnt orange
		];


		const datasets = prefixes.map((prefix, i) => ({
			label: prefix,
			backgroundColor: colorPalette[i % colorPalette.length],
			data: rawData.map((r, idx) =>
				r.sampleClass.startsWith(prefix)
					? logCounts[idx]
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
						display: false
					},
					y: {
						beginAtZero: true,
						title: {
							display: true,
							text: 'Log Number of Samples'
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