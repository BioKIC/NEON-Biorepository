<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLoans.php');
$retMsg = 0;

$collid = $_POST['collid'];
$loanid = $_POST['loanid'];
$catalogNumber = $_POST['catalognumber'];
$processMode = $_POST['processmode'];
$target = $_POST['target'];
#start neon edits
$sampleType = isset($_POST['sampletype']) ? (int)$_POST['sampletype'] : 0;
#end neon edits


if($loanid && $collid && $catalogNumber){
	if($IS_ADMIN
	|| ((array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))
	|| (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor'])))){
		$loanManager = new OccurrenceLoans();
		//$loanManager->setCollId($collid);
		#start neon edits
		if($processMode == 'link') $retMsg = $loanManager->linkSpecimen($loanid, $catalogNumber, $target, $sampleType);
		elseif($processMode == 'checkin') $retMsg = $loanManager->checkinSpecimen($loanid, $catalogNumber, $target, $sampleType);
		elseif($processMode == 'remove') $retMsg = $loanManager->removeSpecimen($loanid, $catalogNumber, $target, $sampleType);
		#end neon edits
	}
}
echo $retMsg;
?>
