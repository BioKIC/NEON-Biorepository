<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/SOWReport.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');

header("Content-Type: text/html; charset=".$CHARSET);

$reports = new SOWReport();
$availableReports = $reports->getAvailableReports(); 
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

if ($isEditor && isset($_POST['generate_report'])) {
    $ay = $reports->generateSOWReport(); 
    header("Location: sowreport.php?ay=" . $ay);
    exit();
}
?>
<html>
<head>
    <title><?php echo $DEFAULT_TITLE; ?> NEON SOW Report</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
    <?php
    $activateJQuery = true;
    include_once($SERVER_ROOT.'/includes/head.php');
    ?>
    <link rel="stylesheet" href="../css/tables.css">
    <script src="../../js/jquery-3.2.1.min.js"></script>
    <script src="../../js/jquery-ui-1.12.1/jquery-ui.min.js"></script>
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
    <h1>NEON SOW Report</h1>

    <form method="get" action="sowreport.php">
        <label for="ay"><strong>Select report AY:</strong></label><br>
        <select name="ay" id="ay" required>
            <option value="">-- Select AY --</option>
            <?php
            foreach ($availableReports as $ay) {
                echo '<option value="' . htmlspecialchars($ay, ENT_QUOTES) . '">' .
                    htmlspecialchars($ay) .
                    '</option>';
            }
            ?>
        </select>
        <br><br>
        <button type="submit">View Existing SOW Report</button>
    </form>

    <form method="post" onsubmit="return confirm('Are you sure you want to generate the new SOW report?');">
        <input type="hidden" name="generate_report" value="1">
        <button type="submit">Generate New SOW Report</button>
    </form>

<?php
} else {
    echo '<h3>Please login to get access to this page.</h3>';
}
?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
<script src="../js/sortables.js"></script>
</body>
</html>
