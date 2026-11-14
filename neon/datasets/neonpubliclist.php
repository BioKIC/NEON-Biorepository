<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/neon/classes/PublicProjectSearch.php');
include_once($SERVER_ROOT.'/content/lang/collections/datasets/publiclist.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$projectSearchConfig = array('endpoint' => $CLIENT_ROOT . '/neon/datasets/rpc/publicprojects.php');
try {
    $projectSearch = new PublicProjectSearch();
    $projectSearchConfig['filters'] = $projectSearch->filters($_GET);
    $projectSearchConfig['options'] = $projectSearch->options();
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    $projectSearchConfig['error'] = $error->getMessage();
} catch (Throwable $error) {
    error_log('Public project filters failed: ' . $error->getMessage());
    $projectSearchConfig['error'] = 'Unable to load project filters. Please reload the page.';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title>Biorepository Research Projects</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
        <style>
            #innertext > h3.MuiTypography-h3 {
                font-size: 2.25rem;
                line-height: 1.3;
            }
            #innertext > p.MuiTypography-body1,
            #innertext > p.MuiTypography-body1 a {
                font-size: 0.875rem;
                line-height: 1.6;
            }
            #project-overview {
                container: project-overview / inline-size;
                margin: 0 0 32px;
                padding: 24px 0 0;
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
                font-size: 0.875rem;
                font-weight: 400;
                line-height: 1.5;
            }
            #project-overview dd {
                order: -1;
                margin: 0 0 8px;
                color: #002c77;
                font-size: 2rem;
                font-weight: 600;
                line-height: 1.2;
                white-space: nowrap;
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
                border-top: 2px dotted #8b9bb3;
                padding-top: 5px;
            }
            #project-overview .overview-note {
                margin: 0;
                font-size: 0.75rem;
                line-height: 1.5;
            }
            @container project-overview (max-width: 900px) {
                #project-overview .overview-metrics {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 24px 0;
                }
                #project-overview .overview-metric:nth-child(3) {
                    border-left: 0;
                }
            }
            @container project-overview (max-width: 540px) {
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
                #project-overview dd { font-size: 1.75rem; }
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
            

			<!--List Projects in React Table-->
			<script>
                window.NEON_PROJECT_SEARCH = <?php echo json_encode(
                    $projectSearchConfig,
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
                ); ?>;
			</script>
            <div id="neon-project-table"></div>
            <noscript>Enable JavaScript to search and browse research projects.</noscript>
		</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
	</body>
</html>
