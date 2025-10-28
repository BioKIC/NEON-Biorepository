<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/InquiriesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/requests/sampleeditor.php');


$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
$request_id = array_key_exists("request_id",$_REQUEST)?$_REQUEST["request_id"]:"";
$ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];

$inquiryManager = new InquiriesManager();

$isEditor = $IS_ADMIN || isset($USER_RIGHTS['SuperAdmin']);

$errStr = '';
$status = '';


if($isEditor && isset($_POST['action'])){
    if($_POST['action'] === 'batchSave'){
        if($inquiryManager->batchEditSamples($_POST)){
            $status = 'close';
        } else {
            $errStr = $inquiryManager->getErrorStr();
        }
    }
    elseif($_POST['action'] === 'deleteSamples'){
        $allDeleted = true;
        foreach($ids as $id){
            if(!$inquiryManager->deleteSample($id)){
                $allDeleted = false;
                $errStr .= "Failed to delete sample #$id. ";
            }
        }
        if($allDeleted){
            $status = 'close';
        }
    }
}

function getCommonValue($samples, $field){
    $values = array_unique(array_column($samples, $field));
    return (count($values) === 1) ? $values[0] : '';
}

$samples = [];
if($ids){
    foreach($ids as $id){
        $samples[] = $inquiryManager->getSampleForEditor($id);
    }
}

$common = [];
foreach(['status','use_type','available','substance_provided','shipment_id','notes'] as $field){
    $common[$field] = getCommonValue($samples, $field);
}
?>
<html>
<head>
<title><?php echo $DEFAULT_TITLE; ?> Batch Sample Editor</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
<?php include_once($SERVER_ROOT.'/includes/head.php'); ?>
<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
<script>
function validateBatchForm(f){

    if(!f.status.value || !f.use_type.value || !f.available.value || !f.substance_provided.value){
        alert("All fields must be assigned during batch update");
        return false;
    }
        if(!f.shipment_id.value && ["current","completed","loaned, not used"].includes(f.status.value)){
        alert("A shipment must be assigned if the status is current, completed, or loaned, not used");
        return false;
    }
    if(f.shipment_id.value && !["current","completed","loaned, not used"].includes(f.status.value)){
        alert("If a shipment is assigned, status must be current, completed, or loaned, not used");
        return false;
    }
    if(!f.notes.value && ["individual(s)","tissue/material sample","subsample/aliquot"].includes(f.substance_provided.value)){
        alert("ERROR: Notes required when substance is tissue/material sample, individual(s), or subsample/aliquot");
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
<form method="post" action="batchsampleeditor.php" onsubmit="return validateBatchForm(this)">
    <?php foreach($ids as $id): ?>
        <input type="hidden" name="ids[]" value="<?= htmlspecialchars($id) ?>">
    <?php endforeach; ?>
    <input type="hidden" name="request_id" value="<?= htmlspecialchars($request_id) ?>">

    <fieldset>
        <legend><b>Batch Update Samples (<?= count($ids) ?> selected)</b></legend>

        <div class="fieldDiv">
            <label><b>Status:</b></label>
            <select name="status" required>
                <option value="">-- choose --</option>
                <?php
                $options = ["pending fulfillment","current","completed","loaned, not used","requested, not found","not funded"];
                foreach($options as $opt){
                    $sel = ($common['status']==$opt?'selected':'');
                    echo "<option value=\"$opt\" $sel>$opt</option>";
                }
                ?>
            </select>
        </div>

        <div class="fieldDiv">
            <label><b>Type of Use:</b></label>
            <select name="use_type" required>
                <option value="">-- choose --</option>
                <?php
                $options = ["non-destructive","invasive","consumptive","destructive"];
                foreach($options as $opt){
                    $sel = ($common['use_type']==$opt?'selected':'');
                    echo "<option value=\"$opt\" $sel>$opt</option>";
                }
                ?>
            </select>
        </div>
        <div class="fieldDiv">
            <label><b>Available:</b></label>
            <select name="available" required>
                <option value="">-- choose --</option>
                <option value="yes" <?= ($common['available']=='yes'?'selected':'') ?>>yes</option>
                <option value="no" <?= ($common['available']=='no'?'selected':'') ?>>no</option>
            </select>
        </div>
        <div style="clear:both;padding-top:6px;float:left;">
        </div>
        <div class="fieldDiv">
            <b>Substance Provided:</b>
            <select name="substance_provided" required>
                <?php $commonSubValue = $common['substance_provided']?>
                <option value="">-----</option>
                <option value="whole sample" <?php if($commonSubValue=='whole sample') echo 'SELECTED'; ?>>whole sample</option>
                <option value="individual(s)" <?php if($commonSubValue=='individual(s)') echo 'SELECTED'; ?>>individual(s) - indicate number in notes</option>
                <option value="tissue/material sample" <?php if($commonSubValue=='tissue/material sample') echo 'SELECTED'; ?>>tissue/material sample - link material samples & indicate tissue type in notes</option>
                <option value="subsample/aliquot" <?php if($commonSubValue=='subsample/aliquot') echo 'SELECTED'; ?>>subsample/aliquot - indicate amount in notes</option>
                <option value="image" <?php if($commonSubValue=='image') echo 'SELECTED'; ?>>image</option>
                <option value="data" <?php if($commonSubValue=='data') echo 'SELECTED'; ?>>data only</option>
                <?php
                if($commonSubValue && !in_array($commonSubValue, array(
                    "whole sample","individual(s)","tissue/material sample","subsample/aliquot","image","data"
                ))){
                    echo '<option value="'.$commonSubValue.'" SELECTED>'.$commonSubValue.'</option>';
                }
                ?>
            </select>
        </div>
        <div class="fieldDiv">
            <label><b>Notes:</b></label>
            <input type="text" name="notes" style="width:400px" value="<?= htmlspecialchars($common['notes'] ?? '', ENT_COMPAT | ENT_HTML401); ?>">
        </div>

        <div class="fieldDiv">
            <label><b>Shipment:</b> (edit request to add new shipment)</label>
            <select name="shipment_id" required>
                <option value="">-- none --</option>
                <?php
                $shipArr = $inquiryManager->getShipmentByID($request_id);
                foreach($shipArr as $shipid => $name){
                    $sel = ($common['shipment_id']==$shipid?'selected':'');
                    echo '<option value="'.htmlspecialchars($shipid).'" '.$sel.'>'.htmlspecialchars($name).'</option>';
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
                if(!empty($s['shipment_id'])){
                    $hasShipment = true;
                    break;
                }
            }
            if($hasShipment){
                echo '<div style="color:red;margin:20px 0px">';
                echo 'Samples can\'t be deleted until all are unlinked from a shipment.';
                echo '</div>';
            }
            else{
                ?>
                <form method="post" action="batchsampleeditor.php"
                    onsubmit="return confirm('Are you sure you want to permanently delete these samples from the request?')">
                    <?php foreach($ids as $id): ?>
                        <input type="hidden" name="ids[]" value="<?= htmlspecialchars($id) ?>">
                    <?php endforeach; ?>
                    <div style="clear:both;margin:15px">
                        <button type="submit" name="action" value="deleteSamples">Delete Samples</button>
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
