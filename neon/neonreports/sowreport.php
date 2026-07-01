<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/SOWReport.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$ay = $_POST['ay'] ?? $_GET['ay'] ?? null;

if (!preg_match('/^\d{4}$/', $ay)) {
    $ay = date('Y');
}

if (!$ay) {
    die('No report AY selected.');
}

$reports = new SOWReport();
$reportDate = $reports->getReportDate($ay);
$reportsArr = $reports->getSOWReport($ay);
$utilities = new Utilities();
$isEditor = false;
if($IS_ADMIN) $isEditor = true;

elseif(array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> NEON SOW Report </title>
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
		<div id="innertext">
<?php
if ($isEditor) {
?>
	<h1>NEON SOW Report: <?php echo htmlspecialchars($month); ?></h1>
 <?php

	if ($reportDate) {
		echo '<p><strong>Report generated: </strong> ' . htmlspecialchars($reportDate) . '</p>';
	}

	if (!empty($reportsArr)) {
		

		$receipts = [];
		$accessioning = [];
		$data  = [];
		$loans = [];

		foreach ($reportsArr as $row) {
			$type = array_shift($row); 

			switch ($type) {
				case 'receipt':
					$receipts[] = $row;
					break;
				case 'accessioning':
					$accessioning[] = $row;
					break;
				case 'data':
					$data[] = $row;
					break;
				case 'loan':
					$loans[] = $row;
					break;
			}
		}
		?>
	<!-- RECEIPTS-->
		<h2>1. Sample Receipt Forms</h2>
			<h4><b>Statement of Work:</h4></p>
				<p><b>Task:</b> Return completed receipt form through the NEON data portal</p>s
				<p><b>AY18:</b> As available</p>
				<p><b>AY19-AY22:</b> As feasible</p>
				<p><b>AY23+:</b> Within 3 months for 90% of samples</p>
		
		<?php
		if ($receipts) {
			$headerArr = ['Award Year', 'No. Shipments','No. Receipts Submitted','Proportion Submitted'];
			echo $utilities->htmlTable($receipts, $headerArr);
		}

		?>
		<form method="post" action="exportreporthandler.php">
			<input type="hidden" name="sow" value="<?= htmlspecialchars($ay, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="receipt">
			<button type="submit">Export Sample Receipt Form Statistics</button>
		</form>

		<h4>Latency to receipt submission</h4>
			<p>** Timestamp of receipt submission was not recorded within our shipment tables until August 1, 2022. Therefore, only shipments sent after that date are included.</p>

			<!--- LATENCY PLOT HERE -->

	
	<!-- ACCESSIONING-->
		<h2>2. Accessioning</h2>
			<h4><b>Statement of Work:</h4></p>
				<p><b>Task:</b> Accession samples received</p>s
				<p><b>AY18:</b> As feasible</p>
				<p><b>AY19-AY22:</b> As feasible</p>
				<p><b>AY23+:</b> Within 3 months for 90% of samples</p>
				<p>Calculated as the number of days between shipment date for the shipment and check in timestamp of the sample. This does overestimate the number of days that the sample was not checked in by the Biorepository because it counts shipping times, but does not allow for occasional underestimates due to lag between shipment receipt and shipment check-in.</p>
				<p>Note that these values ignore (1) all samples from shipments that have not yet been received and (2) all samples marked by the collection manager as not received or not accepted for analysis.</p>
	
		<?php
		if ($accessioning) {
			$headerArr = ['Year', 'No. Samples','No. Checked In', 'Mean Days',"St.D Days",'Median Days','Proprtion All Time','Proportion <30 Days'];
			echo $utilities->htmlTable($accessioning, $headerArr);
		}

		?>
		<form method="post" action="exportreporthandler.php">
			<input type="hidden" name="ay" value="<?= htmlspecialchars($ay, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="accessioning">
			<button type="submit">Export Accessioning Statistics</button>
		</form>

		<h4>Latency for samples that have been checked in</h4>

			<!--- LATENCY PLOT HERE -->

		<h4>Number of samples shipped vs. checked-in per month</h4>
			
			<!--- COMPARISON PLOT HERE -->
	
	<!-- DATA -->
		<h2>3. Sample Data</h2>
			<h4><b>Statement of Work:</h4></p>
				<p><b>Task:</b>Make sample publically available</p>
				<p><b>AY18:</b> As feasible</p>
				<p><b>AY19-AY22:</b> As feasible</p>
				<p><b>AY23+:</b> Within 3 months for 90% of samples</p>
				<p>Calculated as the number of days between shipment date for the shipment and original data harvesting timestamp of the sample. This does overestimate the latency to data availability on the part of the Biorepository because (1) it counts transit times, (2) it ignores latency due to manifest data errors that cause failures to match values in the API, and (3) it ignores lag in data availability in the NEON API, which prevents us from harvesting at the time of check-in.</p>
				<p>Note that these values ignore (1) all samples from shipments that have not yet been received and (2) all samples marked by the collection manager as not received or not accepted for analysis.</p>
		<?php

		if ($data) {
			$headerArr = ['Year', 'No. Samples','No. Available', 'Mean Days',"St.D Days",'Median Days','Proprtion All Time','Proportion <30 Days'];
			echo $utilities->htmlTable($data, $headerArr);
		}
		?>
		<form method="post" action="exportreporthandler.php">
			<input type="hidden" name="ay" value="<?= htmlspecialchars($ay, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="data">
			<button type="submit">Export Sample Data Statistics</button>
		</form>

		<h4>Latency for samples for which data has been harvested</h4>

			<!--- LATENCY PLOT HERE -->

		<h4>Number of samples shipped vs. number of samples for which data became available per month</h4>

			<!--- COMPARISON PLOT HERE -->

	<!-- AVAILABLE FOR LOAN -->
		<h2>4. Loans</h2>
			<h4><b>Statement of Work:</h4></p>
				<p><b>Task:</b>Make samples available for loan</p>
				<p><b>AY18:</b> As feasible</p>
				<p><b>AY19-AY22:</b> As feasible</p>
				<p><b>AY23+:</b> Within 3 months for 90% of samples</p>
				<p>Samples are available for loan as soon as they are checked-in by the collection managers and the data is harvested from the NEON API and published to the Biorepository portal.</p> 
		
	<!-- LOAN FULFILLMENT -->
		<h2>5. Loan Requests</h2>
			<h4><b>Statement of Work:</h4></p>
				<p><b>Task:</b>Fulfill loan requests</p>
				<p><b>AY18:</b> As feasible</p>
				<p><b>AY19-AY22:</b> Within 6 weeks for 90% of requests, except those requiring significant processing or including >100 samples</p>
				<p><b>AY19-AY22:</b> Within 4 weeks for 90% of requests, except those requiring significant processing or including >100 samples</p>
				<p>** Prior to June 2022 only the timestamps for initial inquiry, most recent status update, and shipment were recorded, so time between finalization of the sample list and shipment could not be calculated.</p>

		<?php
		if ($loans) {
			$headerArr = ['Year', 'No. Requests','Mean Days','Median Days','Proprtion <4 wks - All','Proportion <4 wks - Typical*'];
			echo $utilities->htmlTable($loans, $headerArr);
		}
		?>
		<p>*Typical requests are of less than 100 samples and no significant processing </P>
		<form method="post" action="exportreporthandler.php">
			<input type="hidden" name="ay" value="<?= htmlspecialchars($ay, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="loans">
			<button type="submit">Export Loan Request Statistics</button>
		</form>
		<?php

	}
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