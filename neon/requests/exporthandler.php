<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/InquiriesManager.php');

$requestID = (isset($_POST['requestID'])?$_POST['requestID']:'');
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
    if ($exportTask == "materialsamplesrequest"){
	    $inquiryManager->exportMaterialSampleList($requestID);
    }
    elseif($exportTask == "materialsamplestable"){
        $inquiryManager->exportMaterialSampleTable($requestID);
    }
    elseif($exportTask == "pubtable"){
        $inquiryManager->exportPubTable($requestID);
    }
}
?>