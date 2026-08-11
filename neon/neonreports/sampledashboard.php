<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/SampleDashboard.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);


$reports = new SampleDashboard();
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

elseif(array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> NEON Sample Dashboard </title>
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
		<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
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
	<div class="section-nav">
		<a href="#cumulativeSamplesSection">Samples Received</a> |
		<a href="#cumulativeShipmentsSection">Shipments Received</a> |
		<a href="#cumulativeCheckinSection">Sample Check In</a> |
		<a href="#cumulativeRecordsSection">Occurrence Records</a>
        <a href="#cumulativeTaxaSection">Taxa</a>
	</div>
	<h1>NEON Sample Dashboard: <?php echo date('Y-m-d H:i:s'); ?></h1>
 <?php

    $cumulativeSamples = [];

    $result = $reports->cumulativeReceipt();

    while ($row = $result->fetch_assoc()) {
        $cumulativeSamples[] = [
            'x' => $row['sample_date'],
            'y' => (int)$row['cumulative_samples']
        ];
    }

    $samps = $reports->totalSamples();

	if (!empty($cumulativeSamples)) {
    ?>
        <div class="section" id="cumulativeSamplesSection">
            <h2>Cumulative Physical Samples Received To Date: <?= number_format($samps) ?></h2>
                <div style="width: 100%; max-width: 1200px; height: 600px;">
                <canvas id="cumulativeSamples"></canvas>
            </div>
        </div>
    <?php
    }

    $cumulativeShipments = [];

    $result = $reports->cumulativeShipments();

    while ($row = $result->fetch_assoc()) {
        $cumulativeShipments[] = [
            'x' => $row['shipment_date'],
            'y' => (int)$row['cumulative_shipments']
        ];
    }

    $ships = $reports->totalShipments();

	if (!empty($cumulativeShipments)) {
    ?>
        <div class="section" id="cumulativeShipmentsSection">
            <h2>Cumulative Shipments Received To Date: <?= number_format($ships) ?></h2>
            <div style="width: 100%; max-width: 1200px; height: 600px;">
                <canvas id="cumulativeShipments"></canvas>
            </div>
        </div>
    <?php
    }

    $cumulativeCheckIn = [];

    $result = $reports->cumulativeCheckIn();

    while ($row = $result->fetch_assoc()) {
        $cumulativeCheckIn[] = [
            'x' => $row['checkin_date'],
            'y' => (int)$row['cumulative_checkin']
        ];
    }

    $checkin = $reports->totalCheckIn();

	if (!empty($cumulativeCheckIn)) {
    ?>
        <div class="section" id="cumulativeCheckinSection">
            <h2>Cumulative Samples Checked In To Date: <?= number_format($checkin) ?></h2>
            <div style="width: 100%; max-width: 1200px; height: 600px;">
                <canvas id="cumulativeCheckIn"></canvas>
            </div>
        </div>
    <?php
    }

    $cumulativeRecords = [];

    $result = $reports->cumulativeRecords();

    while ($row = $result->fetch_assoc()) {
        $cumulativeRecords[] = [
            'x' => $row['record_date'],
            'y' => (int)$row['cumulative_records']
        ];
    }
    
    $records = $reports->totalRecords();


	if (!empty($cumulativeRecords)) {
    ?>
        <div class="section" id="cumulativeRecordsSection">
            <h2>Cumulative Occurrence Records To Date: <?= number_format($records) ?></h2>
            <div style="width: 100%; max-width: 1200px; height: 600px;">
                <canvas id="cumulativeRecords"></canvas>
            </div>
        </div>
    <?php
    }

    $cumulativeTaxa = [];

    $result = $reports->cumulativeTaxa();

    while ($row = $result->fetch_assoc()) {
        $cumulativeTaxa[] = [
            'x' => $row['taxon_date'],
            'y' => (int)$row['cumulative_taxa']
        ];
    }
    
    $taxa = $reports->totalTaxa();


	if (!empty($cumulativeTaxa)) {
    ?>
        <div class="section" id="cumulativeTaxaSection">
            <h2>Cumulative Taxa To Date: <?= number_format($taxa) ?></h2>
            <div style="width: 100%; max-width: 1200px; height: 600px;">
                <canvas id="cumulativeTaxa"></canvas>
            </div>
        </div>
    <?php
    }
    
}

else {
	echo '<h3>Please login with administrator permissions get access to this page.</h3>';
}
	
?>

</div>
	<?php
		include($SERVER_ROOT.'/includes/footer.php');
	?>
  </body>


<script>
    
    const chartDataCumSamp = <?= json_encode($cumulativeSamples) ?>;

    const ctsamp = document.getElementById('cumulativeSamples');

    new Chart(ctsamp, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Cumulative Samples',
                data: chartDataCumSamp,
                parsing: {
                    xAxisKey: 'x',
                    yAxisKey: 'y'
                },
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                        text: 'Date',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Samples',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                }
            }
        }
    });

    const chartDataCumShip = <?= json_encode($cumulativeShipments) ?>;

    const ctship = document.getElementById('cumulativeShipments');

    new Chart(ctship, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Cumulative Shipments',
                data: chartDataCumShip,
                parsing: {
                    xAxisKey: 'x',
                    yAxisKey: 'y'
                },
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                        text: 'Date',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Shipments',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                }
            }
        }
    });

    const chartDataCumCheck = <?= json_encode($cumulativeCheckIn) ?>;

    const ctcheck = document.getElementById('cumulativeCheckIn');

    new Chart(ctcheck, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Cumulative Samples Checked In',
                data: chartDataCumCheck,
                parsing: {
                    xAxisKey: 'x',
                    yAxisKey: 'y'
                },
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                        text: 'Date',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Samples',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                }
            }
        }
    });

    const chartDataCumRec= <?= json_encode($cumulativeRecords) ?>;

    const ctrec = document.getElementById('cumulativeRecords');

    new Chart(ctrec, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Cumulative Occurrence Records',
                data: chartDataCumRec,
                parsing: {
                    xAxisKey: 'x',
                    yAxisKey: 'y'
                },
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                        text: 'Date',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Occurrence Records',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                }
            }
        }
    });

    const chartDataCumTax= <?= json_encode($cumulativeTaxa) ?>;

    const cttax = document.getElementById('cumulativeTaxa');

    new Chart(cttax, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Cumulative Taxa',
                data: chartDataCumTax,
                parsing: {
                    xAxisKey: 'x',
                    yAxisKey: 'y'
                },
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                        text: 'Date',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Taxa',
                        font: {
                            size: 28
                        }
                    },
                    ticks: {
                        font: {
                            size: 16
                        }
                    }
                }
            }
        }
    });

</script>

<style>
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
    scroll-margin-top: 130px;
}
</style>

</html>
