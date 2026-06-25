<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NeonEditor.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$editManager = new NeonEditor();

$isEditor = 0;
if($IS_ADMIN ){
	$isEditor = 1;
}

$collArr = '';
$collExist = false;


?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE.' Annotation Dashboard' ?></title>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script type="text/javascript">
		</script>
		<style>
			.top-breathing-room-sm-px {
				margin-top: 5px;
			}
			.left-breathing-room-rel-lg {
				margin-left: 2em;
			}
			.fieldset-like > div,
			fieldset > div {
				margin-left: 12px;
			}
		</style>
	</head>
	<body>
	<?php
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Annotation Dashboard</h1>
        <?php
        if($isEditor){
            $collectionArr = $editManager->getAnnotationColls();
            $collExist = !empty($collectionArr);
        ?>
            <div id="colllistdiv" style="min-height:200px;">
                <?php
                if($collExist){
                    echo '</br><div style="font-weight:bold;font-size:120%;">Collections with Annotations in Print Queue</div>';
                    echo '<div><ul>';
                    foreach($collectionArr as $collId => $collArr){
                        echo '<li>';
                        echo '<a href="'.$CLIENT_ROOT.'/collections/reports/annotationmanager.php?collid='.$collId.'">';
                        echo htmlspecialchars($collArr['collectionName']) . ' (' . $collArr['count'] . ')';
                        echo '</a>';
                        echo '</li>';
                    }
                    echo '</ul></div>';
                }
                else{
                    echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There are currently no collections with determinations in the print queue.</div></div>';
                }
                ?>
            </div>
        <?php
		}
        else{
			?>
			<div style="font-weight:bold;margin:20px;font-weight:150%;">
				<?php echo 'You do not have permissions to view annotation dashboard'; ?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');

	?>
	</body>
</html>
