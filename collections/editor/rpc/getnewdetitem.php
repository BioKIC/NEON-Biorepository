<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorDeterminations.php');

$collid = array_key_exists('collid' ,$_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : '';
$catNum = array_key_exists('catalognumber', $_REQUEST) ? htmlspecialchars($_REQUEST['catalognumber'], ENT_QUOTES, 'UTF-8') : '';
$allCatNum = array_key_exists('allcatnum', $_REQUEST) ? filter_var($_REQUEST['allcatnum'], FILTER_SANITIZE_NUMBER_INT) : 0;
$sciName = array_key_exists('sciname', $_REQUEST) ? htmlspecialchars($_REQUEST['sciname'], ENT_QUOTES, 'UTF-8') : '';
$fieldSite = array_key_exists('fieldsite', $_REQUEST) ? filter_var($_REQUEST['fieldsite'], FILTER_SANITIZE_NUMBER_INT) : 0;

$retArr = array();
if($collid){
	$occManager = new OccurrenceEditorDeterminations();
	$occManager->setCollId($collid);
	$retArr = $occManager->getNewDetItem($catNum,$sciName,$allCatNum, $fieldSite);
}

echo json_encode($retArr);
?>