<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OpenIdProfileManager.php');

$profManager = new OpenIdProfileManager();

$sid = array_key_exists('sid', $_REQUEST) ? htmlspecialchars($_REQUEST['sid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$localSessionID = $profManager->lookupLocalSessionIDWithThirdPartySid($sid);
//neon edit
$localSessionID = $_COOKIE['PHPSESSID'] ?? '';
$redirectUrl = GeneralUtil::getDomain() . $CLIENT_ROOT . '/index.php';
//end edit

if($localSessionID){
	$profManager->forceLogout($localSessionID, $sid);
	//neon edit
	//header("HTTP/",true,200);
	header("Location: " . $redirectUrl);
	//end edit
	exit;
}
else{
	header("HTTP/",true,400);
}