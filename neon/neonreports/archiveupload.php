<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/ArchiveUpload.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);


$reports = new ArchiveUpload();

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'addNewSamples'
) {
    $dispositions = $_POST['disposition'] ?? [];

    $reports->addNewArchiveSamples($dispositions);

    header('Location: archiveupload.php');
    exit;
}

$new = $reports->findPotentialNewArchiveSamples();
$unsubmitted = $reports->getNewArchiveSampleTable();
$submitted = $reports->getPriorArchiveSampleTable();
$headerArr = ['archiveGuid','sampleID','sampleCode','sampleClass','sampleFate'];
$utilities = new Utilities();
$isEditor = false;
if($IS_ADMIN) $isEditor = true;

elseif(array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> NEON Archive Upload </title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<?php
		$activateJQuery = true;
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
    <link rel="stylesheet" href="../css/tables.css">
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
        <link rel="stylesheet" href="../../js/datatables/datatables.css" />
        <script src="../../js/datatables/datatables.js"></script>
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
	<h1>NEON Archive Upload Data</h1>
 <?php


	if (!empty($new) || !empty($submitted) || !empty($unsubmitted)) {
		
		echo '<h4><a href=https://data.neonscience.org/web/external-lab-ingest>NEON External Lab Ingest Page</a></h4>';	

		if ($unsubmitted) {
			echo '<h2>Unsubmitted Samples</h2>';
            echo str_replace(
                '<table',
                '<table id="unsubmittedTable"',
                $utilities->htmlTable($unsubmitted, $headerArr)
            );		
		?>
		<form method="post" action="exportarchiveuploadhandler.php">
			<input type="hidden" name="type" value="unsubmitted">
			<button type="submit">Export New Archive Upload Data & Mark Samples as Submitted</button>
		</form>
		<?php
        }

        if ($new) {
			echo '<h2>Potential New Archive Samples</h2>';
            foreach ($new as &$row) {
                $row = array_merge(
                    [
                        'select' => '<input type="checkbox" name="disposition[]" value="' .
                            htmlspecialchars($row['disposition'], ENT_QUOTES) . '">'
                    ],
                    $row
                );
            }
            unset($row);

            $newTypeHeaderArr = ['select', 'disposition', 'count'];

            echo '<form method="post" action="">';
            echo '<input type="hidden" name="action" value="addNewSamples">';

            echo str_replace(
                '<table',
                '<table id="newTable"',
                $utilities->htmlTable($new, $newTypeHeaderArr)
            );

            echo '<button type="submit">Add to Unsubmitted Sample List</button>';
            echo '</form>';
        }

        if ($submitted) {
			echo '<h2>Submitted Samples</h2>';
            echo str_replace(
                '<table',
                '<table id="submittedTable"',
                $utilities->htmlTable($submitted, $headerArr)
            );		

		?>
		<form method="post" action="exportarchiveuploadhandler.php">
			<input type="hidden" name="type" value="submitted">
			<button type="submit">Export Prior Archive Upload Data</button>
		</form>
		<?php
        }

	}
?>

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

<script>
    $(document).ready(function () {
        $('#newTable').DataTable({
            pageLength: 25,
            layout: {
                topStart: {
                    pageLength: {
                        menu: [10, 25, 50, 100, 300, 500, { label: 'All', value: -1 }]
                    }
                }
            },
            scrollCollapse: true
        });
        $('#unsubmittedTable').DataTable({
            pageLength: 25,
            layout: {
                topStart: {
                    pageLength: {
                        menu: [10, 25, 50, 100, 300, 500, { label: 'All', value: -1 }]
                    }
                }
            },
            scrollCollapse: true
        });

        $('#submittedTable').DataTable({
            pageLength: 25,
            layout: {
                topStart: {
                    pageLength: {
                        menu: [10, 25, 50, 100, 300, 500, { label: 'All', value: -1 }]
                    }
                }
            },
            scrollCollapse: true
        });
    });
</script>


</html>