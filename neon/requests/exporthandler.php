<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/requests/list/InquiriesManager.php');

$request_id = (isset($_POST['request_id'])?$_POST['request_id']:'');
$exportTask = $_POST['exportTask'];

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$status = '';
if($isEditor){
	$inquiryManager = new InquiriesManager();
	$inquiryManager->exportSampleList($request_id);
}
?>