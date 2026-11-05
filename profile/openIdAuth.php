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

$_SESSION['NEON_AUTH_FLOW_MODE'] = $isSilentAuth ? 'silent' : 'interactive';
$_SESSION['NEON_AUTH_FLOW_WAS_AUTHENTICATED'] = $wasAuthenticated;

if (!$isSilentAuth) {
	$returnCandidate = $_GET['refurl'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
	$_SESSION['refurl'] = neonAuthGetSafeReturnUrl($returnCandidate, $CLIENT_ROOT);
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
$oidc->authenticate();

if ($isSilentAuth) {
	neonAuthSendSyncResult($wasAuthenticated, false);
}

header('Location: ' . neonAuthGetSafeReturnUrl($_SESSION['refurl'] ?? '', $CLIENT_ROOT));
exit;

