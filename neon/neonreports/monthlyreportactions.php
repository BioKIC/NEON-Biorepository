<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NEONReports.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');

header("Content-Type: text/html; charset=".$CHARSET);

$reports = new NEONReports();
$availableReports = $reports->getAvailableReports('monthly'); 
$utilities = new Utilities();

$isEditor = false;
if($IS_ADMIN || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

if ($isEditor && isset($_POST['generate_report'])) {
    $month = $reports->generateMonthlyReport(); 
    header("Location: monthlyreport.php?month=" . $month);
    exit();
}
?>
<html>
<head>
    <title><?php echo $DEFAULT_TITLE; ?> NEON Monthly Report</title>
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
    <b>NEON Monthly Report</b>
</div>
<div id="innertext">
<?php
if ($isEditor) {
?>
    <h1>NEON Monthly Report</h1>

    <form method="get" action="monthlyreport.php">
        <label for="month"><strong>Select report month:</strong></label><br>
        <select name="month" id="month" required>
            <option value="">-- Select Month --</option>
            <?php
            foreach ($availableReports as $month) {
                echo '<option value="' . htmlspecialchars($month, ENT_QUOTES) . '">' .
                    htmlspecialchars($month) .
                    '</option>';
            }
            ?>
        </select>
        <br><br>
        <button type="submit">View Existing Monthly Report</button>
    </form>

    <form method="post" onsubmit="return confirm('Are you sure you want to generate the new monthly report?');">
        <input type="hidden" name="generate_report" value="1">
        <button type="submit">Generate New Monthly Report</button>
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
