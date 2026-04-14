<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/InquiriesManager.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
header("Content-Type: text/html; charset=".$CHARSET);

$reports = new InquiriesManager();
$utilities = new Utilities();
$referencesArr = $reports->getReferencesOut();
$headerArr = ['id','authors','year','title','samples'];
$total = $reports->getRefSamplesCnt();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;
?>

<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> References </title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
    <link rel="stylesheet" href="css/tables.css">
		<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
        <link rel="stylesheet" href="css/tables.css">

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
        <?php
        echo '<h1>References</h1>';
        echo '<p>Total number of samples associated with references: '.$total.'</p>';

        echo '<input type="text" id="filterInput" placeholder="Search references...">';

        if(!empty($referencesArr)){
            echo '<div class="table-container">';
            $referencestable = $utilities->htmlTable($referencesArr, $headerArr);
            echo $referencestable;
            echo '</div>';
        };
        ?>
				<?php
			} else {
        echo '<h3>Please login to get access to this page.</h3>';
      }
			?>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
  </body>
  <script src="js/sortables.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterInput = document.getElementById('filterInput');
        const table = document.querySelector('table');
        
        filterInput.addEventListener('keyup', function () {
            const filter = filterInput.value.toLowerCase();
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let match = false;
                for (let j = 0; j < cells.length; j++) {
                    const cellValue = cells[j].textContent.toLowerCase();
                    if (cellValue.includes(filter)) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        });
    });
</script>

</html>