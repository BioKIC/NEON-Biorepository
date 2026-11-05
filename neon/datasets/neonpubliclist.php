<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
include_once($SERVER_ROOT.'/content/lang/collections/datasets/publiclist.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$datasetManager = new OccurrenceDataset();
$pArr = $datasetManager->getPublicProjects();

$totalProjects = count($pArr);
$completedProjects = 0;
$institutions = array();
$sampleTypes = array();

foreach ($pArr as $project) {
    if (!empty($project['completeDate'])) {
        $completedProjects++;
    }

    // Ignore blanks and collapse spelling differences in case/whitespace.
    $institution = trim(preg_replace('/\s+/', ' ', $project['institution'] ?? ''));
    if ($institution !== '') {
        $institutions[strtolower($institution)] = true;
    }
    foreach ($project['sampleTypes'] ?? array() as $sampleType) {
        $sampleTypeName = trim(preg_replace('/\s+/', ' ', $sampleType['sampleType']['value'] ?? ''));
        if ($sampleTypeName !== '') {
            $sampleTypes[strtolower($sampleTypeName)] = true;
        }
    }
}

$ongoingProjects = $totalProjects - $completedProjects;
$totalSpecimens = $datasetManager->getProjectSpecimenCount(array_column($pArr, 'datasetid'));

usort($pArr, function ($a, $b) {
	return strtotime($b['activeDate']) <=> strtotime($a['activeDate']);
});
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title>Biorepository Research Projects</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
        <style>
            #project-overview {
                margin: 0 0 40px;
                padding: 32px 0 0;
                background: #fff;
                border-top: 2px dotted #8b9bb3;
                font-family: 'Inter', Helvetica, Arial, sans-serif;
                color: #565a5c;
            }
            #project-overview .overview-metrics {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                margin: 0 0 40px;
            }
            #project-overview .overview-metric {
                display: flex;
                flex-direction: column;
                justify-content: center;
                min-width: 0;
                padding: 8px 16px;
                text-align: center;
            }
            #project-overview .overview-metric + .overview-metric {
                border-left: 2px dotted #8b9bb3;
            }
            #project-overview dt {
                font-size: 1rem;
                font-weight: 400;
                line-height: 1.5;
            }
            #project-overview dd {
                order: -1;
                margin: 0 0 8px;
                color: #002c77;
                font-size: 2.5rem;
                font-weight: 600;
                line-height: 1.2;
                overflow-wrap: anywhere;
                font-variant-numeric: tabular-nums;
            }
            #project-overview .overview-status-bar {
                display: flex;
                height: 8px;
                margin-bottom: 16px;
                background: #d7d9d9;
            }
            #project-overview .overview-footer {
                display: flex;
                flex-wrap: wrap;
                align-items: baseline;
                justify-content: space-between;
                gap: 12px 24px;
            }
            #project-overview .overview-note {
                margin: 0;
                font-size: 0.75rem;
                line-height: 1.5;
            }
            @media (max-width: 900px) {
                #project-overview .overview-metrics {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 24px 0;
                }
                #project-overview .overview-metric:nth-child(3) {
                    border-left: 0;
                }
            }
            @media (max-width: 540px) {
                #project-overview { padding-top: 24px; }
                #project-overview .overview-metrics {
                    grid-template-columns: 1fr;
                    gap: 16px;
                    margin-bottom: 24px;
                }
                #project-overview .overview-metric {
                    padding: 0;
                }
                #project-overview .overview-metric + .overview-metric {
                    border-left: 0;
                    border-top: 2px dotted #8b9bb3;
                    padding-top: 16px;
                }
                #project-overview dd { font-size: 2.25rem; }
            }
        </style>
	</head>
	<body>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<h3 class="MuiTypography-root MuiTypography-h3" style="font-weight: 300;">Biorepository Research Projects</h3>
            <p class="MuiTypography-root MuiTypography-body1" style="margin:30px 0;">
                Each project listed below highlights the breadth of scientific research supported by samples and specimens from the
                <a
                    class="MuiTypography-root MuiLink-root MuiLink-underlineAlways MuiTypography-colorPrimary"
                    href="https://www.neonscience.org/samples"
                    target="_blank"
                >NEON Biorepository</a>. These projects span a wide range of disciplines, sample types, and research questions, demonstrating the many ways NEON samples and specimens can support new discoveries and extend the scientific value of NEON data.
            </p>
            
            <section id="project-overview" aria-label="Public research project overview">
                <dl class="overview-metrics">
                    <div class="overview-metric">
                        <dt>Specimens</dt>
                        <dd><?php echo $totalSpecimens === null ? 'Unavailable' : number_format($totalSpecimens); ?></dd>
                    </div>
                    <div class="overview-metric">
                        <dt>Research projects</dt>
                        <dd><?php echo number_format($totalProjects); ?></dd>
                    </div>
                    <div class="overview-metric">
                        <dt>Institutions</dt>
                        <dd><?php echo number_format(count($institutions)); ?></dd>
                    </div>
                    <div class="overview-metric">
                        <dt>Sample types</dt>
                        <dd><?php echo number_format(count($sampleTypes)); ?></dd>
                    </div>
                </dl>
                <div class="overview-footer">
                    <p class="overview-note">Across all public projects listed below.</p>
                </div>
            </section>

			<!--List Projects in React Table-->
			<script>
                window.tableData = <?php echo json_encode(
                    $pArr ?? array(),
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                ); ?>;
			</script>
            <div id="neon-project-table"></div>
		</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
	</body>
</html>
