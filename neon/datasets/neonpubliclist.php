<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
include_once($SERVER_ROOT.'/content/lang/collections/datasets/publiclist.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$datasetManager = new OccurrenceDataset();
$pArr = $datasetManager->getPublicProjects();

$totalProjects = count($pArr);
$completedProjects = 0;

foreach ($pArr as $project) {
    if (!empty($project['completeDate'])) {
        $completedProjects++;
    }
}

$ongoingProjects = $totalProjects - $completedProjects;

usort($pArr, function ($a, $b) {
	return strtotime($b['activeDate']) <=> strtotime($a['activeDate']);
});
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title>Published Sample Research Datasets</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
        <style>
            #project-overview {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                margin: 0 0 30px;
                padding: 24px 0;
                background: #f5f6f7;
                border-top: 3px solid #002d74;
                font-family: 'Inter', Helvetica, Arial, sans-serif;
            }

            #project-overview > div {
                display: flex;
                flex-direction: column;
                padding: 0 24px;
            }

            #project-overview > div + div {
                border-left: 1px solid #d5d8da;
            }

            #project-overview dt {
                color: #565a5c;
                font-size: 0.875rem;
                font-weight: 400;
            }

            #project-overview dd {
                order: -1;
                margin: 0 0 6px;
                color: #002d74;
                font-size: 2rem;
                font-weight: 300;
                line-height: 1.2;
                font-variant-numeric: tabular-nums;
            }

            @media (max-width: 540px) {
                #project-overview {
                    grid-template-columns: 1fr;
                    padding: 0 20px;
                }

                #project-overview > div {
                    padding: 16px 0;
                }

                #project-overview > div + div {
                    border-left: 0;
                    border-top: 1px solid #d5d8da;
                }
            }
        </style>
	</head>
	<body>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<h3 class="MuiTypography-root MuiTypography-h3" style="font-weight: 300;">NEON Biorepository Research Projects</h3>
            <p class="MuiTypography-root MuiTypography-body1" style="margin:30px 0;">
                Each project listed below highlights the breadth of scientific research supported by samples and specimens from the
                <a
                    class="MuiTypography-root MuiLink-root MuiLink-underlineAlways MuiTypography-colorPrimary"
                    href="https://www.neonscience.org/samples"
                    target="_blank"
                >NEON Biorepository</a>. These projects span a wide range of disciplines, sample types, and research questions, demonstrating the many ways NEON samples and specimens can support new discoveries and extend the scientific value of NEON data.
            </p>
            
            <dl id="project-overview" aria-label="Public research project overview">
                <div>
                    <dt>Total projects</dt>
                    <dd><?php echo number_format($totalProjects); ?></dd>
                </div>
                <div>
                    <dt>Ongoing projects</dt>
                    <dd><?php echo number_format($ongoingProjects); ?></dd>
                </div>
                <div>
                    <dt>Completed projects</dt>
                    <dd><?php echo number_format($completedProjects); ?></dd>
                </div>
            </dl>

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
