<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/InquiriesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/requests/availabilitysync.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');

$action = $_POST['action'] ?? '';
$request_id = $_REQUEST['request_id'] ?? '';
$ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];

$inquiryManager = new InquiriesManager();
$utilities = new Utilities();

$isEditor = $IS_ADMIN || isset($USER_RIGHTS['SuperAdmin']);

$errStr = '';
$status = '';

$samples = [];
if($ids){
    foreach($ids as $id){
        $samples[] = $inquiryManager->getSampleAvailabilityForEditor($id);
    }
}

if ($isEditor && $action) {
    if ($action === 'batchAvailability') {
        if (empty($ids)) {
            $errStr = "No sample IDs provided.";
        } elseif ($inquiryManager->updateAvailability($ids,(int)$SYMB_UID)) {
            $status = 'close';
        } else {
            $errStr = "DB error: " . $inquiryManager->getErrorStr();
        }
    }
    if ($action === 'batchDisposition') {
        $newDisposition = $_POST['disposition'] ?? '';
        if (empty($ids)) {
            $errStr = "No sample IDs provided.";
        } elseif ($newDisposition) {
            if ($inquiryManager->writeDisposition($ids, $newDisposition, (int)$SYMB_UID)) {
                $status = 'close';
            } else {
                $errStr = "DB error: " . $inquiryManager->getErrorStr();
            }
        } else {
            $errStr = "No disposition value provided.";
        }
    }
}
?>
<html>
<head>
<title><?php echo $DEFAULT_TITLE; ?> Sync Availability/Disposition</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
<?php include_once($SERVER_ROOT.'/includes/head.php'); ?>
<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
<style>
fieldset { padding:15px; width:800px; margin: 0 auto; }
.fieldGroupDiv { clear:both; margin-top:2px; height:25px; }
.fieldDiv { float:left; margin-left:10px; }
button { cursor:pointer; }
.table-container { overflow-x: auto; }
table { border-collapse: collapse; width: 100%; margin-top: 1em; font-size: 0.95em; background-color: #fff; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; cursor: pointer; }
tr:nth-child(even) { background-color: #f9f9f9; }
tr:hover { background-color: #f1f1f1; }
</style>
</head>
<body>
<?php if($status === 'close'): ?>
<script>
    alert("Batch edit completed successfully.");
    if(window.opener && !window.opener.closed){
        window.opener.location.reload();
    }
    window.close();
</script>
<?php else: ?>
<form method="post" action="availabilitysync.php">
    <?php foreach($ids as $id): ?>
        <input type="hidden" name="ids[]" value="<?= htmlspecialchars($id) ?>">
    <?php endforeach; ?>
    <input type="hidden" name="request_id" value="<?= htmlspecialchars($request_id) ?>">

    <fieldset>
        <legend><b>Sync Availability (<?= count($ids) ?> selected)</b></legend>
        <div class="fieldDiv">
            <div style="clear:both;padding-top:8px;float:left;">
                <?php
                $idlist = array_column($samples, 'id');
                $availabilitydata = $inquiryManager->getSampleAvailabilityTable($idlist);
                if (!empty($availabilitydata)) {
                    echo '<div class="table-container">';
                    $availabilitytable = $utilities->htmlTable(
                        $availabilitydata, 
                        ['sampleID','sampleCode','availability: request','availability: occurrence']
                    );
                    echo $availabilitytable;
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <div style="clear:both;padding-top:6px;float:left;">
            <button type="submit" name="action" value="batchAvailability">Update Occurrence Availability</button>
        </div>
    </fieldset>

    <fieldset>
        <legend><b>Update Disposition (<?= count($ids) ?> selected)</b></legend>
        <div style="clear:both;padding-top:8px;float:left;">
            <?php
            $dispositiondata = $inquiryManager->getSampleDispositionTable($idlist);
            if (!empty($dispositiondata)) {
                echo '<div class="table-container">';
                $dispositiontable = $utilities->htmlTable(
                    $dispositiondata, 
                    ['sampleID','sampleCode','disposition']
                );
                echo $dispositiontable;
                echo '</div>';
            }
            ?>
        </div>
        <div style="clear:both;padding-top:6px;float:left;">
            <label><b>New Disposition Value:</b></label>
            <input type="text" name="disposition" style="width:400px">
        </div>
        <div style="clear:both;padding-top:6px;float:left;">
            <button type="submit" name="action" value="batchDisposition"
                onclick="return confirm('Are you sure you want to update the disposition of all samples?')">
                Update All Occurrence Disposition Values
            </button>
        </div>
    </fieldset>
</form>

<?php if($errStr): ?>
<div style="color:red;font-weight:bold;margin:10px;">
    <?= $errStr ?>
</div>
<?php endif; ?>
<?php endif; ?>
</body>
</html>

