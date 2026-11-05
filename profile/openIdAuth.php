<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/vendor/autoload.php');
include_once($SERVER_ROOT . '/config/auth_config.php');
include_once($SERVER_ROOT . '/classes/utilities/GeneralUtil.php');
include_once($SERVER_ROOT . '/profile/authUtil.php');
if ($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/profile/openIdAuth.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/profile/openIdAuth.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/profile/openIdAuth.en.php');

use Jumbojett\OpenIDConnectClient;

$AUTH_PROVIDER = $AUTH_PROVIDER ?? 'oid';
$isSilentAuth = isset($_GET['silent']) && $_GET['silent'] === '1';
$wasAuthenticated = !empty($SYMB_UID);
$returnCandidate = $_GET['refurl'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$returnUrl = neonAuthGetSafeReturnUrl($returnCandidate, $CLIENT_ROOT);

$_SESSION['NEON_AUTH_FLOW_MODE'] = $isSilentAuth ? 'silent' : 'interactive';
$_SESSION['NEON_AUTH_FLOW_WAS_AUTHENTICATED'] = $wasAuthenticated;
$_SESSION['NEON_AUTH_RETURN_URL'] = $returnUrl;

if ($isSilentAuth) {
	// Set this before leaving the site so a failed round trip cannot redirect-loop.
	$_SESSION['NEON_AUTH_LAST_SYNC'] = time();
}
else {
	$_SESSION['refurl'] = $returnUrl;
}

$oidc = new OpenIDConnectClient(
	$PROVIDER_URLS[$AUTH_PROVIDER],
	$CLIENT_IDS[$AUTH_PROVIDER],
	$CLIENT_SECRETS[$AUTH_PROVIDER]
);

if ($isSilentAuth) {
	$oidc->addAuthParam(['prompt' => 'none']);
}

$oidc->addScope(['openid']);
$oidc->addScope(['email']);
$oidc->addScope(['profile']);
$oidc->addScope(['read:current_user']);
$oidc->addAuthParam(['audience' => $AUDIENCE]);
$oidc->setResponseTypes(['code']);
$oidc->setRedirectUrl(GeneralUtil::getDomain() . $CLIENT_ROOT . $CALLBACK_REDIRECT);

if (isset($SHOULD_UPGRADE_INSECURE_REQUESTS)) {
	$oidc->setHttpUpgradeInsecureRequests($SHOULD_UPGRADE_INSECURE_REQUESTS);
}
if (isset($SHOULD_VERIFY_PEERS)) {
	$oidc->setVerifyPeer($SHOULD_VERIFY_PEERS);
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
try {
	$oidc->authenticate();
}
catch (Throwable $ex) {
	error_log('OIDC authentication start failed: ' . $ex->getMessage());
	if ($isSilentAuth) {
		neonAuthFinishFlow($returnUrl, true);
	}

	neonAuthClearFlowState();
	$_SESSION['last_message'] = $LANG['CAUGHT_EXCEPTION'] . ' ' . $ex->getMessage() . ' <ERR/>';
	header('Location: ' . $CLIENT_ROOT . '/profile/index.php');
	exit;
}

// authenticate() normally exits after issuing the Auth0 redirect.
neonAuthFinishFlow($returnUrl, $isSilentAuth);

