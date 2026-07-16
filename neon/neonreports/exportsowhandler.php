<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/SOWReport.php');

$ay = $_POST['ay'] ?? '';
$type = $_POST['type'] ?? '';
$reportDate = $_POST['reportDate'] ?? '';

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

if($isEditor){
    $sowreport = new SOWReport();
    $sowreport->exportTable($ay, $type, $reportDate);
}
?>