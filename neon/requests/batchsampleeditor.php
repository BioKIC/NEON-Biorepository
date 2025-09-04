<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/requests/list/InquiriesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) die("Not authorized");

$ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
$request_id = $_POST['request_id'] ?? '';

$inquiryManager = new InquiriesManager();
$isEditor = $IS_ADMIN || isset($USER_RIGHTS['SuperAdmin']);

$errStr = '';
$status = '';

if($isEditor && isset($_POST['action']) && $_POST['action'] == 'batchSave'){
    foreach($ids as $id){
        $data = $_POST;
        $data['id'] = $id;
        if(!$inquiryManager->editSample($data)){
            $errStr .= "Failed to update sample $id: ".$inquiryManager->getErrorStr()."<br/>";
        }
    }
    if(!$errStr) $status = 'close';
}

function getCommonValue($samples, $field){
    $values = array_unique(array_column($samples, $field));
    return (count($values) === 1) ? $values[0] : '';
}

// preload sample values
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
<title>Batch Sample Editor</title>
<script src="../../js/jquery-3.7.1.min.js"></script>
<script>
function validateBatchForm(f){
    if(!f.status.value || !f.use_type.value || !f.available.value || !f.substance_provided.value){
        alert("All fields are required for batch update");
        return false;
    }

    if(f.shipment_id.value && !["current","completed","loaned, not used"].includes(f.status.value)){
        alert("If a shipment is assigned, status must be current, completed, or loaned, not used");
        return false;
    }
    return true;
}
</script>
<style>
fieldset { padding:15px; }
.fieldDiv { margin-bottom:8px; }
</style>
</head>
<body>
<?php if($status === 'close'): ?>
<script>
    alert("Batch update complete");
    if(window.opener && !window.opener.closed){
        window.opener.location.reload();
    }
    window.close();
</script>
<?php else: ?>
<form method="post" action="batchsampleeditor.php" onsubmit="return validateBulkForm(this)">
    <?php foreach($ids as $id): ?>
        <input type="hidden" name="ids[]" value="<?= htmlspecialchars($id) ?>">
    <?php endforeach; ?>
    <input type="hidden" name="request_id" value="<?= htmlspecialchars($request_id) ?>">

    <fieldset>
        <legend>Batch Update Samples (<?= count($ids) ?> selected)</legend>

        <div class="fieldDiv">
            <label>Status:</label>
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
            <label>Use Type:</label>
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
            <label>Available:</label>
            <select name="available" required>
                <option value="">-- choose --</option>
                <option value="yes" <?= ($common['available']=='yes'?'selected':'') ?>>yes</option>
                <option value="no" <?= ($common['available']=='no'?'selected':'') ?>>no</option>
            </select>
        </div>

        <div class="fieldDiv">
            <label>Substance Provided:</label>
            <select name="substance_provided" required>
                <option value="">-- choose --</option>
                <?php
                $options = ["whole sample","individual(s)","tissue/material sample","subsample/aliquot","image","data"];
                foreach($options as $opt){
                    $sel = ($common['substance_provided']==$opt?'selected':'');
                    echo "<option value=\"$opt\" $sel>$opt</option>";
                }
                ?>
            </select>
        </div>

        <div class="fieldDiv">
            <label>Shipment:</label>
            <select name="shipment_id">
                <option value="">-- none --</option>
                <?php
                $shipArr = $inquiryManager->getShipments();
                foreach($shipArr as $shipid => $name){
                    $sel = ($common['shipment_id']==$shipid?'selected':'');
                    echo '<option value="'.htmlspecialchars($shipid).'" '.$sel.'>'.htmlspecialchars($name).'</option>';
                }
                ?>
            </select>
        </div>

        <div class="fieldDiv">
            <label>Notes:</label>
            <input type="text" name="notes" style="width:400px" value="<?= htmlspecialchars($common['notes']) ?>">
        </div>

        <div style="margin-top:10px;">
            <button type="submit" name="action" value="batchSave">Save Changes</button>
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
