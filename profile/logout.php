<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OpenIdProfileManager.php');
include_once($SERVER_ROOT . '/classes/utilities/GeneralUtil.php');
include_once($SERVER_ROOT . '/profile/authUtil.php');

$profManager = new OpenIdProfileManager();
$redirectUrl = GeneralUtil::getDomain() . $CLIENT_ROOT . '/index.php';

// Auth0 OIDC back-channel logout sends a signed logout_token via POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['logout_token'])) {
	include_once($SERVER_ROOT . '/config/auth_config.php');
	require_once($SERVER_ROOT . '/vendor/autoload.php');

	$AUTH_PROVIDER = $AUTH_PROVIDER ?? 'oid';
	$oidc = new Jumbojett\OpenIDConnectClient(
		$PROVIDER_URLS[$AUTH_PROVIDER],
		$CLIENT_IDS[$AUTH_PROVIDER],
		$CLIENT_SECRETS[$AUTH_PROVIDER],
		$PROVIDER_URLS[$AUTH_PROVIDER]
	);

	if (isset($SHOULD_UPGRADE_INSECURE_REQUESTS)) {
		$oidc->setHttpUpgradeInsecureRequests($SHOULD_UPGRADE_INSECURE_REQUESTS);
	}
	if (isset($SHOULD_VERIFY_PEERS)) {
		$oidc->setVerifyPeer($SHOULD_VERIFY_PEERS);
	}

	try {
		if (!$oidc->verifyLogoutToken()) {
			throw new RuntimeException('Invalid logout token');
		}
		$claims = $oidc->getVerifiedClaims();
		$sid = isset($claims->sid) ? $claims->sid : '';
		if ($sid) {
			$localSessionId = $profManager->lookupLocalSessionIDWithThirdPartySid($sid);
			if ($localSessionId) {
				$profManager->forceLogout($localSessionId, $sid);
			}
		}
		header('HTTP/1.1 200 OK');
	}
	catch (Throwable $ex) {
		error_log('OIDC back-channel logout failed: ' . $ex->getMessage());
		header('HTTP/1.1 400 Bad Request');
	}
	exit;
}

// Browser logout return and the legacy sid callback are both idempotent.
$sid = isset($_REQUEST['sid']) && is_string($_REQUEST['sid']) ? $_REQUEST['sid'] : '';
$localSessionId = $sid ? $profManager->lookupLocalSessionIDWithThirdPartySid($sid) : '';
if (!$localSessionId && !empty($_COOKIE[session_name()])) {
	$localSessionId = $_COOKIE[session_name()];
}

if ($localSessionId) {
	$profManager->forceLogout($localSessionId, $sid);
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: ' . $redirectUrl);
exit;

