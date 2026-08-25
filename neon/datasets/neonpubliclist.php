<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
include_once($SERVER_ROOT.'/content/lang/collections/datasets/publiclist.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$datasetManager = new OccurrenceDataset();
$pArr = $datasetManager->getPublicProjects();

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
                <br><br>
                The projects listed here are led by PIs who have opted in to listing their project publicly. 
            </p>
            
			<!--List Projects in React Table-->
			<script>
                window.tableData = <?php echo json_encode(
                    $pArr ?? array(),
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                ); ?>;
			</script>
            <div id="neon-project-table"></div>
		</div>
	</body>
</html>
