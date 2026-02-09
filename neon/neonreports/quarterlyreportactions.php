<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');

header("Content-Type: text/html; charset=".$CHARSET);

$reports = new NEONReports();
$availableReports = $reports->getAvailableReports('quarterly'); 
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

if ($isEditor && isset($_POST['generate_report'])) {
    $quarter = $reports->generateQuarterlyReport(); 
    header("Location: quarterlyreport.php?quarter=" . $quarter);
    exit();
}
?>
<html>
<head>
    <title><?php echo $DEFAULT_TITLE; ?> NEON Quarterly Sample Use Report</title>
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
<div class="navpath">
    <a href="../../../index.php">Home</a> &gt;&gt;
    <a href="../index.php">Management Tools</a> &gt;&gt;
    <b>NEON Quarterly Sample Use Report</b>
</div>
<div id="innertext">
<?php
if ($isEditor) {
?>
    <h1>NEON Quarterly Sample Use Report</h1>

    <form method="get" action="quarterlyreport.php">
        <label for=""><strong>Select report quarter:</strong></label><br>
        <select name="quarter" id="quarter" required>
            <option value="">-- Select quarter --</option>
            <?php
            foreach ($availableReports as $quarter) {
                echo '<option value="' . htmlspecialchars($quarter, ENT_QUOTES) . '">' .
                    htmlspecialchars($quarter) .
                    '</option>';
            }
            ?>
        </select>
        <br><br>
        <button type="submit">View Existing Quarterly Report</button>
    </form>

    <form method="post" onsubmit="return confirm('Are you sure you want to generate the new quarterly report?');">
        <input type="hidden" name="generate_report" value="1">
        <button type="submit">Generate New Quarterly Report</button>
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
