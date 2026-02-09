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
		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
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

	?>
	<h2>Summary:</h2>
	<?php
	$summary = $reports->generateQuarterlyReportSummary($quarter,$reportDate);
	echo '<p>' . $summary . '</p>';

	if (!empty($reportsArr)) {

		$tables = [];

		foreach ($reportsArr as $row) {
			$period    = $row['period'];
			$tabletype = $row['tabletype'];

			$tables[$tabletype][$period][] = $row;
		}

		$excludeTableTypes = [
				'Sample Use By Initiation Year Bar Chart',
				'Sample Use By Status Year Bar Chart'
			];


		foreach ($tables as $tableType => $periodData) {

			if (in_array($tableType, $excludeTableTypes, true)) {
				continue;
			}

			echo '<h2>' . htmlspecialchars($tableType) . '</h2>';

			$cleaned = [];

			foreach ($periodData as $period => $rows) {

				if (str_contains($quarter, 'Q1') && ($period === 'Award Year' || $period === 'Prior Award Year')) {
				 	continue;
				}

				foreach ($rows as $r) {

					unset(
						$r['pk'],
						$r['name'],
						$r['period'],
						$r['tabletype'],
						$r['date']
					);

					$cleaned[$period][] = $r;
				}

				$cleaned[$period] = $reports->removeNullColumns($cleaned[$period]);

			}

			$sampleRow = null;

			foreach ($cleaned as $rows) {
				if (!empty($rows)) {
					$sampleRow = $rows[0];
					break;
				}
			}

			if (!$sampleRow) continue;

			$rowKeys = array_keys($sampleRow);
			$rowLabelKey = $rowKeys[0];
			$valueKeys = array_slice($rowKeys, 1);

			$allLabels = [];

			foreach ($cleaned as $rows) {
				foreach ($rows as $r) {
					$allLabels[$r[$rowLabelKey]] = true;
				}
			}

			$allLabels = array_keys($allLabels);

			usort($allLabels, function($a, $b) {

				if ($a === 'Total Unique') return 1;
				if ($b === 'Total Unique') return -1;

				return strcasecmp($a, $b);
			});

			$headers = [ucwords(str_replace('_',' ',$rowLabelKey))];

			$year = (int) substr($quarter, 2, 2);

			foreach ($cleaned as $period => $rows) {
				foreach ($valueKeys as $valKey) {
					if ($period === 'Quarter') $title = $quarter;
					if ($period === 'Prior Quarter') {
						$newYear = $year - 1; 
						$title = 'AY' . str_pad($newYear, 2, '0', STR_PAD_LEFT) . substr($quarter, 4);
					}
					elseif ($period === 'Award Year') {
						$title = substr($quarter,0,4); 
						if($tableType == 'Requests by Status Comparisons by AY'){
							$title = 'AY' . str_pad($year, 2, '0', STR_PAD_LEFT) . ' Q1-' . substr($quarter, 4);
						}
					}
					elseif ($period === 'Prior Award Year') {
						$newYear = $year - 1; 
						$title = 'AY' . str_pad($newYear, 2, '0', STR_PAD_LEFT) . ' Q1-' . substr($quarter, 4);
					}
					elseif ($period === 'To Date') $title = 'To Date';
					if ($valKey == 'physicalSamples') $valKey = 'Physical Samples'; 
					$headers[] = $title . ':<br>' . ucwords($valKey);
				}
			}

			$finalRows = [];

			foreach ($allLabels as $label) {

				$rowOut = [$label];

				foreach ($cleaned as $period => $rows) {

					$match = null;

					foreach ($rows as $r) {
						if ($r[$rowLabelKey] == $label) {
							$match = $r;
							break;
						}
					}

					foreach ($valueKeys as $valKey) {
						$rowOut[] = $match[$valKey] ?? 0;
					}
				}

				$finalRows[] = $rowOut;
			}

			if ($tableType == 'Researchers and Requests by Status') {
				echo '<p>The number of requests for a given status are those newly reaching that maximum status during the indicated period. Completed 
				requests are only included within the active request category if they were also 
				newly active within the period. Researchers includes all researchers associated with those requests. 
				Researchers may be repeated across different statuses.</p>';
			}
			elseif ($tableType == 'Researchers and Samples by Collection'){
				echo '<p>The number of researchers is the total number of researchers involved in any requests that are newly active
				or pending within the period and associated with the collection.  Researchers may be repeated across 
				collections but are unique within a collection. "Samples" indicate the number of samples associated with the 
				included requests. "Physical Samples" removes samples for which only images or Biorepository-collected data are explicitly involved in research, 
				excluding requests entirely for outreach or internal purposes. In either case, samples" may be repeated if they are involved in multiple requests. 
				Samples values of zero indicate that the collection is involved only in pending requests for which samples have not yet been identified</p>';
			}
				if ($tableType == 'Samples by Primary Research Field') {
					echo '<p>Sample numbers are calculated as in the Researchers and Samples by Collection table</p>';
			}

			echo $utilities->htmlTable($finalRows, $headers);


			echo '<form method="post" action="exportquarterlyreporthandler.php" style="margin-bottom:20px;">
				<input type="hidden" name="quarter" value="' . htmlspecialchars($quarter, ENT_QUOTES) . '">
				<input type="hidden" name="tabletype" value="' . htmlspecialchars($tableType, ENT_QUOTES) . '">
				<button type="submit">Download table above as CSV</button>
				</form>';		
		
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

	<h2>Cumulative Requests</h2>

	<div style="height: 450px; max-width: 1000px;">
		<canvas id="cumulativeRequests"></canvas>
	</div>

	<h2>Cumulative Sample Use</h2>

	<div style="height: 450px; max-width: 1000px;">
		<canvas id="cumulativeSampleRequests"></canvas>
	</div>

	<h2>Requests by Initiation Award Year</h2>

	<div style="height: 450px; max-width: 1000px;">
		<canvas id="requestsByInitiationAY"></canvas>
	</div>

	<h2>Requests by Award Year of Status Update</h2>

	<div style="height: 450px; max-width: 1000px;">
		<canvas id="requestsByStatusAY"></canvas>
	</div>
	
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

<?php
$chartDataInit = [];

foreach ($reportsArr as $row) {
    if ($row['tabletype'] === 'Sample Use By Initiation Year Bar Chart') {
        $chartDataInit[] = [
            'initiationAY' => (string)$row['initiationOrStatusAY'],
            'status'       => $row['status'],
            'requests'     => (int)$row['requests']
        ];
    }
}
?>

<script>
    const chartDataInit_<?= md5($reportDate) ?> = <?= json_encode($chartDataInit) ?>;
</script>

<script>
	(function () {

		const rawData = chartDataInit_<?= md5($reportDate) ?>;

		const years = [...new Set(rawData.map(r => r.initiationAY))].sort();

		const statuses = [
			'active/complete',
			'pending funding/fulfillment',
			'not funded',
			'initial inquiry only'
		];
		const colorPalette = {
			'initial inquiry only': '#dfdfe0',
			'active/complete': '#0472cf',
			'pending funding/fulfillment': '#d18710ff',
			'not funded': '#4b372f'
		};

		const datasets = statuses.map(status => ({
			label: status,
			backgroundColor: colorPalette[status] || '#999999',
			data: years.map(year => {
				const row = rawData.find(
					r => r.initiationAY === year && r.status === status
				);
				return row ? row.requests : 0;
			})
		}));

		const ctx = document
			.getElementById('requestsByInitiationAY')
			.getContext('2d');

		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: years,
				datasets: datasets
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					x: {
						stacked: true,
						title: {
							display: true,
							text: 'Award Year of Initiation'
						}
					},
					y: {
						stacked: true,
						beginAtZero: true,
						title: {
							display: true,
							text: 'Number of Requests'
						}
					}
				},
				plugins: {
					legend: {
						position: 'bottom'
					},
					tooltip: {
						callbacks: {
							label: function (ctx) {
								return ctx.dataset.label + ': ' +
									ctx.raw.toLocaleString();
							}
						}
					}
				}
			}
		});

	})();
</script>

<?php
$chartDataStat = [];

foreach ($reportsArr as $row) {
    if ($row['tabletype'] === 'Sample Use By Status Year Bar Chart') {
        $chartDataStat[] = [
            'statusAY' => (string)$row['initiationOrStatusAY'],
            'status'       => $row['status'],
            'requests'     => (int)$row['requests']
        ];
    }
}
?>

<script>
    const chartDataStat_<?= md5($reportDate) ?> = <?= json_encode($chartDataStat) ?>;
</script>

<script>
	(function () {

		const rawData = chartDataStat_<?= md5($reportDate) ?>;

		const years = [...new Set(rawData.map(r => r.statusAY))].sort();

		const statuses = [
			'active/complete',
			'pending funding/fulfillment',
			'not funded',
			'initial inquiry only'
		];
		const colorPalette = {
			'initial inquiry only': '#dfdfe0',
			'active/complete': '#0472cf',
			'pending funding/fulfillment': '#d18710ff',
			'not funded': '#4b372f'
		};

		const datasets = statuses.map(status => ({
			label: status,
			backgroundColor: colorPalette[status] || '#999999',
			data: years.map(year => {
				const row = rawData.find(
					r => r.statusAY === year && r.status === status
				);
				return row ? row.requests : 0;
			})
		}));

		const ctx = document
			.getElementById('requestsByStatusAY')
			.getContext('2d');

		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: years,
				datasets: datasets
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					x: {
						stacked: true,
						title: {
							display: true,
							text: 'Award Year of Status Update'
						}
					},
					y: {
						stacked: true,
						beginAtZero: true,
						title: {
							display: true,
							text: 'Number of Requests'
						}
					}
				},
				plugins: {
					legend: {
						position: 'bottom'
					},
					tooltip: {
						callbacks: {
							label: function (ctx) {
								return ctx.dataset.label + ': ' +
									ctx.raw.toLocaleString();
							}
						}
					}
				}
			}
		});

	})();
</script>

	<?php
		$chartDataCumReq = $reports->getCumulativeRequests($reportDate);
	?>
		<script>
		const chartDataCumReq_<?= md5($reportDate) ?> = <?= json_encode($chartDataCumReq) ?>;
		</script>

<script>
(function () {

    const rawData = chartDataCumReq_<?= md5($reportDate) ?>;

    const allInquiries = rawData
        .filter(r => r.statustype === 'all inquiries')
        .map(r => ({
            x: r.date,
            y: r.rank
        }))
        .sort((a, b) => new Date(a.x) - new Date(b.x));

    const activeRequests = rawData
        .filter(r => r.statustype === 'active requests')
        .map(r => ({
            x: r.date,
            y: r.rank
        }))
        .sort((a, b) => new Date(a.x) - new Date(b.x));

    const ctx = document
        .getElementById('cumulativeRequests')
        .getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'All inquiries',
                    data: allInquiries,
                    borderColor: '#000000',
                    backgroundColor: '#000000',
                    pointRadius: 0,
                    tension: 0.2
                },
                {
                    label: 'Active requests',
                    data: activeRequests,
                    borderColor: '#0472cf',
                    backgroundColor: '#0472cf',
                    pointRadius: 0,
                    tension: 0.2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'nearest',
                intersect: false
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'year',
                        tooltipFormat: 'yyyy-MM-dd'
                    },
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cumulative Number of Requests'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

})();
</script>

</script>

	<?php
		$chartDataCumSamp = $reports->getCumulativeSamplesRequests($reportDate);
	?>
		<script>
		const chartDataCumSamp_<?= md5($reportDate) ?> = <?= json_encode($chartDataCumSamp) ?>;
		</script>

<script>
(function () {

    const rawData = chartDataCumSamp_<?= md5($reportDate) ?>;

    const allSamps = rawData
        .filter(r => r.type === 'all sample use')
        .map(r => ({
            x: r.date,
            y: r.samples
        }))
        .sort((a, b) => new Date(a.x) - new Date(b.x));

    const resSamps = rawData
        .filter(r => r.type === 'research use of physical samples')
        .map(r => ({
            x: r.date,
            y: r.samples
        }))
        .sort((a, b) => new Date(a.x) - new Date(b.x));

    const ctx = document
        .getElementById('cumulativeSampleRequests')
        .getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'All Sample Use',
                    data: allSamps,
                    borderColor: '#000000',
                    backgroundColor: '#000000',
                    pointRadius: 0,
                    tension: 0.2
                },
                {
                    label: 'Excluding use of only images/data and use for internal or outreach purposes',
                    data: resSamps,
                    borderColor: '#0472cf',
                    backgroundColor: '#0472cf',
                    pointRadius: 0,
                    tension: 0.2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'nearest',
                intersect: false
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'year',
                        tooltipFormat: 'yyyy-MM-dd'
                    },
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cumulative Number of Samples'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

})();
</script>



</html>