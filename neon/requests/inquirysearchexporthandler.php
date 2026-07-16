<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/RequestReport.php');

$ids = (isset($_POST['ids'])?$_POST['ids']:'');
$exportTask = $_POST['exportTask'] ?? '';

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

if($isEditor){
	$reportManager = new RequestReportManager();
    if ($exportTask == "inquiries"){
	    $reportManager->exportInquiryList($ids);
    }
    elseif($exportTask == "samples"){
        $reportManager->exportSampleList($ids);
    }
    elseif($exportTask == "occurrences"){
        $reportManager->exportOccurrenceList($ids);
    }
    elseif ($exportTask == "materialsamples"){
	    $reportManager->exportMaterialSampleList($ids);
    }
}
?>