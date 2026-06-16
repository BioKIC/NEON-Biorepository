<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/RequestReport.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$reportManager = new RequestReportManager();
$utilities = new Utilities();
$inquiriesArr = $reportManager->filterSearchInquiries($_POST);
$ids = array_column($inquiriesArr, 'requestid');
$idsString = implode(',', $ids ?: []);
$headerArr = ['id','researcher','inquiry date','title','status','samples','follow up type','follow up date'];

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>

<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Sample Use Inquiries</title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
    <link rel="stylesheet" href="css/tables.css">
		<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
        <link rel="stylesheet" href="../../js/datatables/datatables.css" />
        <script src="../../js/datatables/datatables.js"></script>

<style>
    .table-container {
        overflow-x: auto;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 1em;
        font-size: 0.95em;
        background-color: #fff;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
        cursor: pointer;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    #filterInput {
        padding: 8px;
        margin-top: 10px;
        width: 100%;
        max-width: 300px;
        font-size: 1em;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
    }

    .toolbar form {
        margin: 0;
    }

    .toolbar button {
        white-space: nowrap;
    }

    #filterInput {
        margin-left: auto;
        width: 300px;
        max-width: 100%;
    }
</style>

	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		?>
	<div id="innertext">
	<?php
	if($isEditor){
	?>
        <h1>Sample Use Inquiries</h1>
            <div class="toolbar">

                <form action="inquirysearchexporthandler.php" method="post">
                    
                    <input type="hidden" name="ids"
                        value="<?= htmlspecialchars($idsString) ?>" />
                    <input type="hidden" name="exportTask" value="inquiries" />
                    <button type="submit" name="action" value="inquiries">
                        Export Inquiries
                    </button>
                </form>

                <form action="inquirysearchexporthandler.php" method="post">
                    <input type="hidden" name="ids"
                        value="<?= htmlspecialchars($idsString) ?>" />
                    <input type="hidden" name="exportTask" value="samples" />
                    <button type="submit" name="action" value="samples">
                        Export Samples
                    </button>
                </form>

                <form action="inquirysearchexporthandler.php" method="post">
                    <input type="hidden" name="ids"
                        value="<?= htmlspecialchars($idsString) ?>" />
                    <input type="hidden" name="exportTask" value="occurrences" />
                    <button type="submit" name="action" value="occurrences">
                        Export Occurrences
                    </button>
                </form>

                <form action="inquirysearchexporthandler.php" method="post">
                    <input type="hidden" name="ids"
                        value="<?= htmlspecialchars($idsString) ?>" />
                    <input type="hidden" name="exportTask" value="materialsamples" />
                    <button type="submit" name="action" value="materialsamples">
                        Export Material Samples
                    </button>
                </form>
            </div>    
            

        <?php

        if (!empty($inquiriesArr)) {
            foreach ($inquiriesArr as &$row) {
                unset($row['requestid']);
            }
            unset($row);

            $inquiriesTable = $utilities->htmlTable($inquiriesArr, $headerArr);

            if ($inquiriesTable) {
                $inquiriesTable = str_replace(
                    '<table',
                    '<table id="inquiriesTable"',
                    $inquiriesTable
                );
                echo $inquiriesTable;
            }
            echo '</div>';
        }

	} else {
        echo '<h3>You do not have permissions to view this page.</h3>';
    }
		?>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
  </body>
  <script src="js/sortables.js"></script>

  <script>
    $(document).ready(function () {
        $('#inquiriesTable').DataTable({
            pageLength: 100,
            order: [[0, 'desc']],
            scrollX: true
        });
    }); 
</script>

</html>