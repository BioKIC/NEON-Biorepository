<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/NeonEditor.php');

$catNum = array_key_exists('catalognumber', $_REQUEST) ? $_REQUEST['catalognumber'] : '';
$allCatNum = array_key_exists('allcatnum', $_REQUEST) ? filter_var($_REQUEST['allcatnum'], FILTER_SANITIZE_NUMBER_INT) : 0;
$sciName = array_key_exists('sciname', $_REQUEST) ? $_REQUEST['sciname'] : '';
$fieldSite = array_key_exists('fieldsite', $_REQUEST) ? filter_var($_REQUEST['fieldsite'], FILTER_SANITIZE_NUMBER_INT) : 0;

$retArr = array();
$occManager = new NeonEditor();
$retArr = $occManager->getNewDetItem($catNum,$sciName,$allCatNum, $fieldSite);

echo json_encode($retArr);
?>