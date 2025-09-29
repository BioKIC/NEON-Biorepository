<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/requests/InquirySampleLoadManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

// redirect if not logged in
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/requests/sampleloader.php');

$action = array_key_exists("action",$_REQUEST) ? $_REQUEST["action"] : "";
$request_id = array_key_exists('id', $_REQUEST) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT) : '';
$isEditor = ($IS_ADMIN) ? true : false;

$inquiryLoadManager = new InquirySampleLoadManager();
$inquiryLoadManager->setRequestID($request_id);

$fieldMap = array();
if($isEditor){
    if(array_key_exists("sf", $_POST)){
        $targetFields = $_POST["tf"];
        $sourceFields = $_POST["sf"];
        for($x = 0; $x < count($targetFields); $x++){
            if($targetFields[$x] && $sourceFields[$x]) $fieldMap[$sourceFields[$x]] = $targetFields[$x];
        }
        $inquiryLoadManager->setFieldMap($fieldMap);
    }
}

// ===== Handle file persistence =====
$savedFile = '';
if(!empty($_POST['savedfile'])){
    $savedFile = $_POST['savedfile'];
    $inquiryLoadManager->setSavedFile($savedFile);
} elseif(!empty($_FILES['uploadfile']['tmp_name']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])){
    // Move uploaded file to a temp folder
    $uploadTmp = $_FILES['uploadfile']['tmp_name'];
    $destDir = $SERVER_ROOT.'/temp_uploads';
    if(!file_exists($destDir)) mkdir($destDir, 0777, true);
    $savedFile = $destDir.'/inquiry_'.$request_id.'_'.time().'.csv';
    move_uploaded_file($uploadTmp, $savedFile);
    $inquiryLoadManager->setSavedFile($savedFile);
}
?>
<html>
<head>
    <title><?php echo $DEFAULT_TITLE; ?> Sample Loader </title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
    <?php include_once($SERVER_ROOT.'/includes/head.php'); ?>
    <script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
    <script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
    <script type="text/javascript">
    function verifyUploadForm(f){
        var fileName = f.uploadfile.value;
        if(fileName == ""){
            alert("Select a sample file to upload");
            return false;
        }
        var ext = fileName.split('.').pop().toLowerCase();
        if(ext == "xlsx" || ext == "xls"){
            alert("Unable to import Excel files (.xlsx, .xls). Save file in the CSV format.");
            return false;
        } else if(ext != "csv"){
            return confirm("Is the import file in the CSV format? If not, select cancel, save file in CSV format, and reimport.");
        }
        return true;
    }

    function verifyMappingForm(f){
        var sfArr=[], tfArr=[];
        for(var i=0;i<f.length;i++){
            var obj = f.elements[i];
            if(obj.name=="sf[]"){
                if(obj.value.trim()!="" && sfArr.indexOf(obj.value)>-1){
                    alert("ERROR: Source field names must be unique (duplicate: "+obj.value+")");
                    return false;
                }
                sfArr.push(obj.value);
            } else if(obj.name=="tf[]" && obj.value!="" && obj.value!="unmapped"){
                if(tfArr.indexOf(obj.value)>-1){
                    alert("ERROR: Can't map to same target field '"+obj.value+"' more than once");
                    return false;
                }
                tfArr.push(obj.value);
            }
        }
    }
    </script>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
    <a href="../../index.php">Home</a> &gt;&gt;
    <a href="../../neon/index.php">Management Tools</a> &gt;&gt;
    <a href="../../neon/requests/inquiries.php">Inquiry List</a> &gt;&gt;
    <a href="inquiryform.php?id=<?php echo $request_id; ?>">Inquiry Record</a> &gt;&gt;
    <a href="samplelist.php?id=<?php echo $request_id; ?>">Sample List</a> &gt;&gt;
    <b>Sample Loader</b>
</div>

<?php
if($isEditor){
?>
<div id="innertext">
    <h1>Sample Loader</h1>
    <div style="margin:30px;">
<?php
    if($action == 'Map Input File'){
        $analyzeStatus = $inquiryLoadManager->analyzeUpload();
        $errCode = 1;
        if(!$analyzeStatus){
            $errStr = $inquiryLoadManager->getErrorStr();
            echo '<div style="font-weight:bold">ERROR analyzing import file: '.$errStr.'</div>';
            $errCode = 0;
        }
        if($errCode){
?>
<div id="mappingFormDiv">
    <form name="mappingform" action="sampleloader.php" method="post" onsubmit="return verifyMappingForm(this)">
        <fieldset style="width:90%;">
            <legend style="font-weight:bold;font-size:120%;">Sample Upload Form for Inquiry # <?php echo $request_id?></legend>
            <table class="styledtable" style="width:350px;">
                <tr><th>Source Field</th><th>Target Field</th></tr>
<?php
        $sourceArr = $inquiryLoadManager->getSourceArr();
        $targetArr = $inquiryLoadManager->getTargetArr();
        $translationMap = array('request_id'=>'request_id','occid'=>'occid','status'=>'status','use_type'=>'use_type','substance_provided'=>'substance_provided',
                                'available'=>'available','notes'=>'notes','shipment_id'=>'shipment_id');
        foreach($sourceArr as $sourceField){
            $translatedSourceField = strtolower($sourceField);
            if(array_key_exists($translatedSourceField,$translationMap)) $translatedSourceField=$translationMap[$translatedSourceField];
            $bgColor='yellow';
            if($inquiryLoadManager->array_key_iexists($translatedSourceField,$fieldMap)) $bgColor='white';
            elseif($inquiryLoadManager->in_iarray($translatedSourceField,$targetArr)) $bgColor='white';
?>
<tr>
    <td><?php echo $sourceField; ?><input type="hidden" name="sf[]" value="<?php echo $sourceField; ?>" /></td>
    <td>
        <select name="tf[]" style="background:<?php echo $bgColor; ?>">
            <option value="">Field Unmapped</option>
            <option value="">-------------------------</option>
<?php
$matchTerm = $inquiryLoadManager->array_key_iexists($translatedSourceField,$fieldMap) ? strtolower($fieldMap[$translatedSourceField]) : $translatedSourceField;
foreach($targetArr as $targetField){
    echo '<option '.($matchTerm==strtolower($targetField)?'SELECTED':'').'>'.$targetField.'</option>';
}
?>
        </select>
    </td>
</tr>
<?php } ?>
            </table>
            <div style="margin:10px;">
                <input type="checkbox" name="reloadSamples" value="1" /> Reload sample record if it already exists
            </div>
            <!-- Keep uploaded file path and request_id -->
            <input type="hidden" name="savedfile" value="<?php echo htmlspecialchars($savedFile); ?>" />
            <input type="hidden" name="id" value="<?php echo (int)$request_id; ?>" />
            <div style="margin:10px;">
                <input type="submit" name="action" value="Process Samples" />
            </div>
        </fieldset>
    </form>
</div>
<?php
    } // end Map Input File
} elseif($action=='Process Samples'){
    echo '<ul>';
    $inquiryLoadManager->setFieldMap($fieldMap);
    $inquiryLoadManager->uploadData();
    echo '</ul>';
} else {
?>
<div>
    <form name="uploadform" action="sampleloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyUploadForm(this)">
        <fieldset style="width:90%;">
            <legend style="font-weight:bold;font-size:120%;">Sample Upload Form</legend>
            <input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
            <div style="margin:10px;">
                <input id="genuploadfile" name="uploadfile" type="file" size="40" />
            </div>
            <div style="margin:10px;">
                <input type="hidden" name="id" value="<?php echo (int)$request_id; ?>" />
                <input type="submit" name="action" value="Map Input File" />
            </div>
        </fieldset>
    </form>
</div>
<?php } ?>
    </div>
</div>
<?php
} else {
?>
<div style='font-weight:bold;margin:30px;'>
    You do not have permissions to upload samples to a request
</div>
<?php } ?>

<?php include($SERVER_ROOT.'/includes/footer.php'); ?>
</body>
</html>
