<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OpenIdProfileManager.php');

$profManager = new OpenIdProfileManager();

$sid = array_key_exists('sid', $_REQUEST) ? htmlspecialchars($_REQUEST['sid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$localSessionID = $profManager->lookupLocalSessionIDWithThirdPartySid($sid);
//neon edit
$localSessionID = $_COOKIE['PHPSESSID'] ?? '';
$redirectUrl = GeneralUtil::getDomain() . $CLIENT_ROOT . '/index.php';

$_SESSION = [];            // empty the session array

// remove the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// destroy the session file on the server
session_destroy();
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