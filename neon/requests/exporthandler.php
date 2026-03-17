<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/InquiriesManager.php');

$requestID = (isset($_POST['requestID'])?$_POST['requestID']:'');
$type = (isset($_POST['type'])?$_POST['type']:'');
$pubID = (isset($_POST['pubID'])?$_POST['pubID']:'');
$exportTask = $_POST['exportTask'];

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$status = '';
if($isEditor){
	$inquiryManager = new InquiriesManager();
    if ($exportTask == "samplesrequest"){
	    $inquiryManager->exportSampleList($requestID);
    }
    elseif($exportTask == "occurrences"){
        $inquiryManager->exportOccurList($requestID);
    }
    elseif ($exportTask == "materialsamplesrequest"){
	    $inquiryManager->exportMaterialSampleList($requestID);
    }
    elseif($exportTask == "materialsamplestable"){
        $inquiryManager->exportMaterialSampleTable($requestID);
    }
    elseif($exportTask == "pubtable"){
        $inquiryManager->exportPubTable($pubID,$type);
    }
}
?>