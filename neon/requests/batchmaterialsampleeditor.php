<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/InquiriesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/requests/materialsampleeditor.php');


$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
$requestID = array_key_exists("requestID",$_REQUEST)?$_REQUEST["requestID"]:"";
$ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];

$inquiryManager = new InquiriesManager();

$isEditor = $IS_ADMIN || isset($USER_RIGHTS['SuperAdmin']);

$errStr = '';
$status = '';

define('BATCH_NOCHANGE', '__NOCHANGE__');
define('BATCH_MULTIPLE', '__MULTIPLE__');
define('BATCH_REMOVE', '__REMOVE__');

if($isEditor && isset($_POST['action'])){
    if($_POST['action'] === 'batchSave'){
        $allowed = ['status','useType','sampleType','shipmentID','notes'];
        $updates = [];
        foreach ($allowed as $field) {
            if (!isset($_POST[$field])) {
                continue;
            }
            $val = $_POST[$field];
            if (
                $val === BATCH_NOCHANGE ||
                $val === BATCH_MULTIPLE ||
                $val === ''
            ) {
                continue;
            }
            if ($field === 'shipmentID' && $val === BATCH_REMOVE) {
                $updates[$field] = null;
                continue;
            }
            $updates[$field] = $val;
        }

        if (!$updates) {
            $errStr = 'No changes selected.';
        }
        elseif ($inquiryManager->batchEditMaterialSamples($updates, $ids)) {
            $status = 'close';
        }
        else {
            $errStr = $inquiryManager->getErrorStr();
        }
    }
    elseif($_POST['action'] === 'deleteMaterialSamples'){
        $allDeleted = true;
        foreach($ids as $id){
            if(!$inquiryManager->deleteMaterialSample($id)){
                $allDeleted = false;
                $errStr .= "Failed to delete material sample #$id. ";
            }
        }
        if($allDeleted){
            $status = 'close';
        }
    }
}

function getCommonValue($samples, $field){
    $values = array_unique(array_column($samples, $field));

    if (count($values) === 1) {
        return $values[0] === '' ? BATCH_NOCHANGE : $values[0];
    }

    return BATCH_MULTIPLE;
}


$samples = [];
if($ids){
    foreach($ids as $id){
        $samples[] = $inquiryManager->getMaterialSampleForEditor($id);
    }
}

$common = [];
foreach(['status','useType','sampleType','shipmentID','notes'] as $field){
    $common[$field] = getCommonValue($samples, $field);
}
?>
<html>
<head>
<title><?php echo $DEFAULT_TITLE; ?> Batch Material Sample Editor</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
<?php include_once($SERVER_ROOT.'/includes/head.php'); ?>
<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
<script>
function validateBatchForm(f){

    if (
        f.status.value === "__NOCHANGE__" &&
        f.useType.value === "__NOCHANGE__" &&
        f.sampleType.value === "__NOCHANGE__" &&
        f.shipmentID.value === "__NOCHANGE__" &&
        !f.notes.value
    ) {
            alert("No changes selected.");
            return false;
    }

    if(!f.status.value || !f.useType.value || !f.sampleType.value){
        alert("All fields must be assigned during batch update");
        return false;
    }
    const hasShipment =
        f.shipmentID.value &&
        f.shipmentID.value !== "__NOCHANGE__" &&
        f.shipmentID.value !== "__REMOVE__";

    if (!hasShipment && ["current","complete"].includes(f.status.value)) {
        alert("A shipment must be assigned if the status is current or complete");
        return false;
    }

    if (hasShipment && !["current","complete"].includes(f.status.value)) {
        alert("If a shipment is assigned, status must be current or complete");
        return false;
    }
    
    return true;
}
</script>
<style>
fieldset { padding:15px; width:800px; margin: 0 auto; }
.fieldGroupDiv { clear:both; margin-top:2px; height:25px; }
.fieldDiv { float:left; margin-left:10px; }
button { cursor:pointer; }
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
<form method="post" action="batchmaterialsampleeditor.php" onsubmit="return validateBatchForm(this)">
    <?php foreach($ids as $id): ?>
        <input type="hidden" name="ids[]" value="<?= htmlspecialchars($id) ?>">
    <?php endforeach; ?>
    <input type="hidden" name="requestID" value="<?= htmlspecialchars($requestID) ?>">

    <fieldset>
        <legend><b>Batch Update Material Samples (<?= count($ids) ?> selected)</b></legend>

        <div class="fieldDiv">
            <label><b>Status:</b></label>
            <select name="status" required>
                <option value="<?= BATCH_NOCHANGE ?>" selected>-- do not change --</option>

                <?php if ($common['status'] === BATCH_MULTIPLE): ?>
                    <option value="<?= BATCH_MULTIPLE ?>" disabled>-- multiple values --</option>
                <?php endif; ?>                
                <?php
                $options = ["pending fulfillment","current","complete","pending funding"];
                foreach($options as $opt){
                    $sel = ($common['status']==$opt?'selected':'');
                    echo "<option value=\"$opt\" $sel>$opt</option>";
                }
                ?>
            </select>
        </div>

        <div class="fieldDiv">
            <label><b>Type of Use:</b></label>
            <select name="useType" required>
                <option value="<?= BATCH_NOCHANGE ?>" selected>-- do not change --</option>

                <?php if ($common['usetype'] === BATCH_MULTIPLE): ?>
                    <option value="<?= BATCH_MULTIPLE ?>" disabled>-- multiple values --</option>
                <?php endif; ?>
                <?php
                $options = ["non-destructive","invasive","consumptive","destructive"];
                foreach ($options as $opt) {
                    $sel = ($common['usetype'] === $opt) ? 'selected' : '';
                    echo "<option value=\"$opt\" $sel>$opt</option>";
                }
                ?>
            </select>
        </div>
        <div style="clear:both;padding-top:6px;float:left;">
        </div>
        <div class="fieldDiv">
            <b>Sample Type:</b>
                <?php 
                $sampleTypes = $inquiryManager->getMaterialSampleTypes();
                ?>
                <select name="sampleType" required>
                    <option value="<?= BATCH_NOCHANGE ?>" selected>-- do not change --</option>

                    <?php if ($common['sampletype'] === BATCH_MULTIPLE): ?>
                     <option value="<?= BATCH_MULTIPLE ?>" disabled>-- multiple values --</option>
                    <?php endif; ?>
                    <?php foreach($sampleTypes as $type => $label):
                        $selected = ($type == $common['sampletype']) ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $selected ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
        </div>
        <div class="fieldDiv">
            <label><b>Notes:</b></label>
            <input
                type="text" name="notes" style="width:400px"
                placeholder="-- do not change --"
                value="<?= ($common['notes'] !== BATCH_MULTIPLE ? htmlspecialchars((string)($notes ?? '')): '') ?>"
            >        
        </div>
        <div class="fieldDiv">
            <label><b>Shipment:</b> (edit request to add new shipment)</label>
            <select name="shipmentID" required>
                <option value="<?= BATCH_NOCHANGE ?>" selected>-- do not change --</option>

                <?php if ($common['shipmentid'] === BATCH_MULTIPLE): ?>
                    <option value="<?= BATCH_MULTIPLE ?>" disabled>-- multiple values --</option>
                <?php endif; ?>
                <option value="<?= BATCH_REMOVE ?>">-- remove shipment --</option>
                <?php
                $shipArr = $inquiryManager->getShipmentByID($requestID);
                foreach($shipArr as $shipid => $name){
                    $sel = ($common['shipmentid']==$shipid?'selected':'');
                    echo '<option value="'.htmlspecialchars((string)($shipid ?? '')).'" '.$sel.'>'.htmlspecialchars((string)($name ?? '')).'</option>';
                }
                ?>
            </select>
        </div>
        <div style="clear:both;padding-top:6px;float:left;">
        </div>
        <div class="fieldGroupDiv">
            <div class="fieldDiv">
                <button type="submit" name="action" value="batchSave">Save Changes</button>
            </div>
        </div>
        </form>
        <fieldset style="width:800px;margin-left:auto;margin-right:auto;">
            <legend><b>Delete From Request (<?= count($ids) ?> selected)</b></legend>
            <?php
            $hasShipment = false;
            foreach($samples as $s){
                if(!empty($s['shipmentID'])){
                    $hasShipment = true;
                    break;
                }
            }
            if($hasShipment){
                echo '<div style="color:red;margin:20px 0px">';
                echo 'Material samples can\'t be deleted until all are unlinked from a shipment.';
                echo '</div>';
            }
            else{
                ?>
                <form method="post" action="batchmaterialsampleeditor.php"
                    onsubmit="return confirm('Are you sure you want to permanently delete these samples from the request?')">
                    <?php foreach($ids as $id): ?>
                        <input type="hidden" name="ids[]" value="<?= htmlspecialchars($id) ?>">
                    <?php endforeach; ?>
                    <div style="clear:both;margin:15px">
                        <button type="submit" name="action" value="deleteMaterialSamples">Delete Material Samples</button>
                    </div>
                </form>
                <?php
            }
            ?>
        </fieldset>

<?php if($errStr): ?>
<div style="color:red;font-weight:bold;margin:10px;">
    <?= $errStr ?>
</div>
<?php endif; ?>
<?php endif; ?>
</body>
</html>
