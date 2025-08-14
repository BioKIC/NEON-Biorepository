<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
include_once($SERVER_ROOT.'/content/lang/collections/datasets/publiclist.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$datasetManager = new OccurrenceDataset();
$dArr = $datasetManager->getPublicDatasets();
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
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php"> <?php echo htmlspecialchars($LANG['H_HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?> </a> &gt;&gt;
			<b> <?php echo htmlspecialchars($LANG['PUB_DAT_LIST'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?> </b>
		</div>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<h1 class="page-heading"><?php echo htmlspecialchars($LANG['PUB_DAT_LIST'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?></h1>
			<ul>
				<?php
				if ($IS_ADMIN) {
					echo '<p><a href="index.php">Dataset Management</a></p>';
				}

					echo '<p>The datasets below link to samples and specimens associated with published research and special collections. Visit the <b><a href="https://scholar.google.com/citations?user=MGg_jIcAAAAJ&hl=en&oi=ao">NEON Biorepository Google Scholar Profile</a></b> for an up-to-date list of publications related to NEON samples and specimens.</p>';


				if($dArr){
					$catArr = array();
					// Creates categories array
					foreach($dArr as $row) {
						if (array_key_exists('category', $row)) {
							($row['category']) ? array_push($catArr, $row['category']) : array_push($catArr, NULL);
						}
						else {
							echo '<li><a href="public.php?datasetid=' . htmlspecialchars($row['datasetid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($row['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
						}
					}
					if (count($catArr) > 1) {
						$catArr = array_unique($catArr);
						foreach($catArr as $cat) {
							echo ($cat) ? '<h3>'.$cat.'</h3>' : '';
							foreach($dArr as $row){
								if ($cat === $row['category']) {
									echo '<li><a href="public.php?datasetid=' . htmlspecialchars($row['datasetid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($row['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
								}
							}
						}
					}
					else {
						echo '<li><a href="public.php?datasetid=' . htmlspecialchars($row['datasetid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($row['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
					}
				}
				?>
			</ul>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
<script>
	let pubTools = document.getElementById('pubtools');
	// toggle visibility of save button
	pubTools.addEventListener('click', function() {});
</script>
</html>
