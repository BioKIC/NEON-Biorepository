<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/SOWReport.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$ay = $_POST['ay'] ?? $_GET['ay'] ?? null;

if (!$ay) {
    die('No report AY selected.');
}

$reports = new SOWReport();
$reportDate = $reports->getReportDate($ay);
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
		<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
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
	<h1>NEON SOW Report: AY<?php echo htmlspecialchars($ay); ?></h1>
	<div class="section-nav">
		<a href="#receipts">1. Sample Receipt Forms</a> |
		<a href="#accessioning">2. Accessioning</a> |
		<a href="#data">3. Sample Data</a> |
		<a href="#loans">4. Loans</a> |
		<a href="#loanrequests">5. Loan Requests</a>
	</div>
 <?php

	if ($reportDate) {
		echo '<p><strong>Report generated: </strong> ' . htmlspecialchars($reportDate) . '</p>';
	}

	if (!empty($reportDate)) {
		$receipts = $reports->getSOWReport($ay,'receipts', $reportDate);
		$receiptLatency = $reports->receiptPlot($reportDate);
		$accessioning = $reports->getSOWReport($ay,'accessioning', $reportDate);
		$accessionLatency = $reports->checkinPlot($reportDate);
		$data = $reports->getSOWReport($ay,'data', $reportDate);
		$dataLatency = $reports->dataPlot($reportDate);
		$loans = $reports->getSOWReport($ay,'loans', $reportDate);

		?>
	<!-- RECEIPTS-->
		<div class="section">
			<h2>1. Sample Receipt Forms</h2>

			<h3>Statement of Work</h3>

			<div class="details" id="receipts">
				<p><strong>Task:</strong> Return completed receipt form through the NEON data portal</p>
				<p><strong>AY18:</strong> As available</p>
				<p><strong>AY19–AY22:</strong> As feasible</p>
				<p><strong>AY23+:</strong> Within 3 months for 90% of samples</p>
			</div>
		</div>
		
		<?php
		if ($receipts) {
			$headerArr = ['Award Award Year', 'No. Shipments','No. Receipts Submitted','Percent Submitted'];
			echo $utilities->htmlTable($receipts, $headerArr);
		}

		?>
		<form method="post" action="exportsowhandler.php">
			<input type="hidden" name="ay" value="<?= htmlspecialchars($ay, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="receipts">
			<input type="hidden" name="reportDate"     value="<?= htmlspecialchars($reportDate, ENT_QUOTES) ?>">
			<button type="submit">Export Sample Receipt Form Statistics</button>
		</form>

		<h3>Latency to receipt submission</h3>

			<div class="details">
				<p>** Timestamp of receipt submission was not recorded within our shipment tables until August 1, 2022. Therefore, only shipments sent after that date are included.</p>
			</div>
			<canvas id="receiptLatencyChart" height="100"></canvas>
	
	<!-- ACCESSIONING-->
	 	<div class="section" id="accessioning">
			<h2>2. Accessioning</h2>

			<h3>Statement of Work</h3>

			<div class="details">
				<p><strong>Task:</strong> Accession samples received</p>
				<p><strong>AY18:</strong> As feasible</p>
				<p><strong>AY19-AY22:</strong> As feasible</p>
				<p><strong>AY23+:</strong> Within 3 months for 90% of samples</p>
				<p>Calculated as the number of days between shipment date for the shipment and check in timestamp of the sample. This does overestimate the number of days that the sample was not checked in by the Biorepository because it counts shipping times, but does not allow for occasional underestimates due to lag between shipment receipt and shipment check-in.</p>
				<p>Note that these values ignore (1) all samples from shipments that have not yet been received and (2) all samples marked by the collection manager as not received or not accepted for analysis.</p>
			</div>
		</div>

		<?php
		if ($accessioning) {
			$headerArr = ['Award Year', 'No. Samples','No. Checked In', 'Mean Days',"St.D Days",'Percent All Time','Percent <3 Months'];
			echo $utilities->htmlTable($accessioning, $headerArr);
		}

		?>
		<form method="post" action="exportsowhandler.php">
			<input type="hidden" name="ay" value="<?= htmlspecialchars($ay, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="accessioning">
			<input type="hidden" name="reportDate"     value="<?= htmlspecialchars($reportDate, ENT_QUOTES) ?>">
			<button type="submit">Export Accessioning Statistics</button>
		</form>

		<h3>Latency for samples that have been checked in</h3>
		
			<canvas id="accessionLatencyChart" height="100"></canvas>

		<!-- <h3>Number of samples shipped vs. checked-in per month</h3> -->
			
			<!--- COMPARISON PLOT HERE -->
	
	<!-- DATA -->
	 	<div class="section" id="data">
			<h2>3. Sample Data</h2>

			<h3>Statement of Work</h3>

			<div class="details">
				<p><strong>Task:</strong> Make samples publicly available</p>
				<p><strong>AY18:</strong> As feasible</p>
				<p><strong>AY19-AY22:</strong> As feasible</p>
				<p><strong>AY23+:</strong> Within 3 months for 90% of samples</p>
				<p>Calculated as the number of days between shipment date for the shipment and original data harvesting timestamp of the sample. This does overestimate the latency to data availability on the part of the Biorepository because (1) it counts transit times, (2) it ignores latency due to manifest data errors that cause failures to match values in the API, and (3) it ignores lag in data availability in the NEON API, which prevents us from harvesting at the time of check-in.</p>
				<p>Note that these values ignore (1) all samples from shipments that have not yet been received and (2) all samples marked by the collection manager as not received or not accepted for analysis.</p>
			</div>
		</div>		
		<?php

		if ($data) {
			$headerArr = ['Award Year', 'No. Samples','No. Available', 'Mean Days',"St.D Days",'Percent All Time','Percent <3 Months'];
			echo $utilities->htmlTable($data, $headerArr);
		}
		?>
		<form method="post" action="exportsowhandler.php">
			<input type="hidden" name="ay" value="<?= htmlspecialchars($ay, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="data">
			<input type="hidden" name="reportDate"     value="<?= htmlspecialchars($reportDate, ENT_QUOTES) ?>">
			<button type="submit">Export Sample Data Statistics</button>
		</form>

		<h3>Latency for samples for which data has been harvested</h3>

			<canvas id="dataLatencyChart" height="100"></canvas>

		<!-- <h3>Number of samples shipped vs. number of samples for which data became available per month</h3> --->

			<!--- COMPARISON PLOT HERE -->

	<!-- AVAILABLE FOR LOAN -->

		<div class="section" id="loans">
			<h2>4. Loans</h2>

			<h3>Statement of Work</h3>

			<div class="details">
				<p><strong>Task:</strong> Make samples available for loan</p>
				<p><strong>AY18:</strong> As feasible</p>
				<p><strong>AY19-AY22:</strong> As feasible</p>
				<p><strong>AY23+:</strong> Within 3 months for 90% of samples</p>
				<p>Samples are available for loan as soon as they are checked-in by the collection managers and the data is harvested from the NEON API and published to the Biorepository portal.</p> 
			</div>
		</div>	
		
	<!-- LOAN FULFILLMENT -->
	 	<div class="section" id="loansrequests">
			<h2>5. Loan Requests</h2>

			<h3>Statement of Work</h3>

			<div class="details">
				<p><strong>Task:</strong> Fulfill loan requests</p>
				<p><strong>AY18:</strong> As feasible</p>
				<p><strong>AY19-AY22:</strong> Within 6 weeks for 90% of requests, except those requiring significant processing or including >100 samples</p>
				<p><strong>AY19-AY22:</strong> Within 4 weeks for 90% of requests, except those requiring significant processing or including >100 samples</p>
				<p>** Prior to June 2022 only the timestamps for initial inquiry, most recent status update, and shipment were recorded, so time between finalization of the sample list and shipment could not be calculated.</p>
			</div>
		</div>	

		<?php
		if ($loans) {
			$headerArr = ['Award Year', 'No. Requests','Mean Days','Median Days','Percent <4 wks - All','Percent <4 wks - Typical*'];
			echo $utilities->htmlTable($loans, $headerArr);
		}
		?>
		<p>*Typical requests are of less than 100 samples and no significant processing </P>
		<form method="post" action="exportsowhandler.php">
			<input type="hidden" name="ay" value="<?= htmlspecialchars($ay, ENT_QUOTES) ?>">
			<input type="hidden" name="type" value="loans">
			<input type="hidden" name="reportDate"     value="<?= htmlspecialchars($reportDate, ENT_QUOTES) ?>">
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

<script>
	const receipLatency = <?= json_encode($receiptLatency) ?>;

	console.log(receipLatency);

	const receiptCtx = document.getElementById('receiptLatencyChart');

	new Chart(receiptCtx, {
		type: 'scatter',
		data: {
			datasets: [
				{
					label: 'Receipt Latency',
					data: receipLatency,
					pointRadius: 3
				},
				{
					label: '90-Day Goal',
					type: 'line',
					data: [
						{
							x: receipLatency[0].x,
							y: 90
						},
						{
							x: receipLatency[receipLatency.length - 1].x,
							y: 90
						}
					],
					borderColor: 'red',
					borderWidth: 2,
					pointRadius: 0,
					borderDash: [8, 6]
				}
			]
		},
		options: {
			plugins: {
				legend: {
					display: false
				}
			},
			scales: {
				x: {
					type: 'time',
					time: {
						unit: 'month'
					},
					title: {
						display: true,
						text: 'Shipment Date'
					}
				},
				y: {
					title: {
						display: true,
						text: 'Days to Receipt Submission'
					}
				}
			}
		}
	});

	const accessionLatency = <?= json_encode($accessionLatency) ?>;

	console.log(accessionLatency);

	const accessionCtx = document.getElementById('accessionLatencyChart');

	new Chart(accessionCtx, {
		type: 'scatter',
		data: {
			datasets: [
				{
					label: 'Accessioning Latency',
					data: accessionLatency,
					pointRadius: 3,
					pointBackgroundColor: 'rgb(54, 162, 235)',
					pointBorderColor: 'rgb(54, 162, 235)',
					pointBorderWidth: 0,
					showLine: false
				},
				{
					label: '90-Day Goal',
					type: 'line',
					data: [
						{
							x: accessionLatency[0].x,
							y: 90
						},
						{
							x: accessionLatency[accessionLatency.length - 1].x,
							y: 90
						}
					],
					borderColor: 'red',
					borderWidth: 2,
					pointRadius: 0,
					borderDash: [8, 6]
				}
			]
		},
		options: {
			plugins: {
				legend: {
					display: false
				}
			},
			scales: {
				x: {
					type: 'time',
					time: {
						unit: 'month'
					},
					title: {
						display: true,
						text: 'Shipment Month'
					}
				},
				y: {
					title: {
						display: true,
						text: 'Shipment Mean Days to Sample Check In'
					}
				}
			}
		}
	});

	const dataLatency = <?= json_encode($dataLatency) ?>;

	console.log(dataLatency);

	const dataCtx = document.getElementById('dataLatencyChart');

	new Chart(dataCtx, {
		type: 'scatter',
		data: {
			datasets: [
				{
					label: 'DataLatency',
					data: dataLatency,
					pointRadius: 3,
					pointBackgroundColor: 'rgb(54, 162, 235)',
					pointBorderColor: 'rgb(54, 162, 235)',
					pointBorderWidth: 0,
					showLine: false
				},
				{
					label: '90-Day Goal',
					type: 'line',
					data: [
						{
							x: dataLatency[0].x,
							y: 90
						},
						{
							x: dataLatency[dataLatency.length - 1].x,
							y: 90
						}
					],
					borderColor: 'red',
					borderWidth: 2,
					pointRadius: 0,
					borderDash: [8, 6]
				}
			]
		},
		options: {
			plugins: {
				legend: {
					display: false
				}
			},
			scales: {
				x: {
					type: 'time',
					time: {
						unit: 'month'
					},
					title: {
						display: true,
						text: 'Shipment Month'
					}
				},
				y: {
					title: {
						display: true,
						text: 'Shipment Mean Days to Data Availability'
					}
				}
			}
		}
	});


</script>


<style>

.section h2 {
    margin-bottom: 1rem;
}

.section h3 {
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.details {
    margin-left: 2rem;  
}

.details p {
    margin: 0.4rem 0;
    color: #666;         
    font-size: 0.95rem;
    line-height: 1.5;
}

.details strong {
    color: #333;         
    font-weight: 600;
}
.section-nav {
    margin: 20px 0;
    padding: 10px 15px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.section-nav a {
    text-decoration: none;
    color: #0056b3;
    font-weight: 600;
    margin-right: 12px;
}

.section-nav a:hover {
    text-decoration: underline;
}

.section {
    scroll-margin-top: 80px; 
}
</style>


</html>