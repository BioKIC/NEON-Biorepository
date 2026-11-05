<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OpenIdProfileManager.php');
include_once($SERVER_ROOT . '/config/auth_config.php');
include_once($SERVER_ROOT . '/classes/utilities/GeneralUtil.php');
include_once($SERVER_ROOT . '/profile/authUtil.php');
require_once($SERVER_ROOT . '/vendor/autoload.php');

use Jumbojett\OpenIDConnectClient;

if ($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/profile/authCallback.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/profile/authCallback.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/profile/authCallback.en.php');

$profManager = new OpenIdProfileManager();
$AUTH_PROVIDER = $AUTH_PROVIDER ?? 'oid';
$isSilentAuth = ($_SESSION['NEON_AUTH_FLOW_MODE'] ?? '') === 'silent';
$wasAuthenticated = !empty($_SESSION['NEON_AUTH_FLOW_WAS_AUTHENTICATED']);
$returnUrl = neonAuthGetSafeReturnUrl(
	$_SESSION['NEON_AUTH_RETURN_URL'] ?? ($_SESSION['refurl'] ?? ''),
	$CLIENT_ROOT
);

$oidc = new OpenIDConnectClient(
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

if (!empty($_REQUEST['error'])) {
	$errorCode = is_string($_REQUEST['error']) ? $_REQUEST['error'] : 'authentication_error';
	$stateValid = neonAuthValidateAndClearErrorState($_REQUEST['state'] ?? '');
	if (!$stateValid) {
		error_log('OIDC error callback rejected because its state was invalid');
		if ($isSilentAuth) {
			neonAuthFinishFlow($returnUrl, true);
		}
		neonAuthClearFlowState();
		$_SESSION['last_message'] = $LANG['AUTHENTICATION_FAILED'] . ' <ERR/>';
		header('Location: ' . $CLIENT_ROOT . '/profile/index.php');
		exit;
	}
	if ($isSilentAuth) {
		if ($errorCode === 'login_required' && $wasAuthenticated) {
			neonAuthClearLocalSession($profManager);
		}
		neonAuthFinishFlow($returnUrl, true);
	}

	neonAuthClearFlowState();
	$_SESSION['last_message'] = $LANG['AUTHENTICATION_FAILED'] . ' <ERR/>';
	header('Location: ' . $CLIENT_ROOT . '/profile/index.php');
	exit;
}

if (empty($_REQUEST['code'])) {
	if ($isSilentAuth) {
		neonAuthFinishFlow($returnUrl, true);
	}
	neonAuthClearFlowState();
	$_SESSION['last_message'] = $LANG['AUTHENTICATION_FAILED'] . ' <ERR/>';
	header('Location: ' . $CLIENT_ROOT . '/profile/index.php');
	exit;
}

try {
	$status = $oidc->authenticate();
	$claims = $oidc->getVerifiedClaims();
	$sid = isset($claims->sid) ? $claims->sid : '';
}
catch (Throwable $ex) {
	if ($isSilentAuth) {
		error_log('Silent OIDC authentication failed: ' . $ex->getMessage());
		neonAuthFinishFlow($returnUrl, true);
	}
	neonAuthClearFlowState();
	$_SESSION['last_message'] = $LANG['CAUGHT_EXCEPTION'] . ' ' . $ex->getMessage() . ' <ERR/>';
	header('Location: ' . $CLIENT_ROOT . '/profile/index.php');
	exit;
}

if (!$status) {
	if ($isSilentAuth) {
		neonAuthFinishFlow($returnUrl, true);
	}
	neonAuthClearFlowState();
	$_SESSION['last_message'] = $LANG['AUTHENTICATION_FAILED'] . ' <ERR/>';
	header('Location: ' . $CLIENT_ROOT . '/profile/index.php');
	exit;
}

$_SESSION['ACCESS_TOKEN'] = $oidc->getAccessToken();
$sub = $oidc->requestUserInfo('sub');
$_SESSION['SUBSCRIBER'] = $sub;
$_SESSION['AUTH_PROVIDER'] = $AUTH_PROVIDER;
$_SESSION['AUTH_CLIENT_ID'] = $oidc->getClientID();
$_SESSION['AUTH_SID'] = $sid;

$localAuthenticated = $profManager->authenticate($sub, $PROVIDER_URLS[$AUTH_PROVIDER]);

if ($localAuthenticated) {
	$profManager->linkThirdPartySid($sid, session_id(), $_SERVER['REMOTE_ADDR']);
	$firstName = $oidc->requestUserInfoByID($sub, 'given_name')
		?? $oidc->requestUserInfoByID($sub, 'user_metadata.first_name');
	$lastName = $oidc->requestUserInfoByID($sub, 'family_name')
		?? $oidc->requestUserInfoByID($sub, 'user_metadata.last_name');
	$organization = $oidc->requestUserInfoByID($sub, 'user_metadata.ror_id')
		?? $oidc->requestUserInfoByID($sub, 'user_metadata.organization');

	$profManager->updateLocalUserFromAuth0Metadata(
		$sub,
		$oidc->getProviderURL(),
		$firstName,
		$lastName,
		$organization,
		$oidc->requestUserInfoByID($sub, 'user_metadata.affiliation'),
		$oidc->requestUserInfoByID($sub, 'user_metadata.organization_country'),
		$oidc->requestUserInfoByID($sub, 'user_metadata.subject_matter_expertise_provider'),
		$oidc->requestUserInfoByID($sub, 'user_metadata.orcid')
	);
}
else {
	$email = $oidc->requestUserInfo('email');
	if ($email) {
		try {
			$linked = $profManager->linkLocalUserOidSub(
				$email,
				$sub,
				$oidc->getProviderURL(),
				$oidc->requestUserInfo('nickname'),
				$oidc->requestUserInfoByID($sub, 'user_metadata.first_name'),
				$oidc->requestUserInfoByID($sub, 'user_metadata.last_name')
			);
			$profManager->updateLocalUserFromAuth0Metadata(
				$sub,
				$oidc->getProviderURL(),
				$oidc->requestUserInfoByID($sub, 'user_metadata.first_name'),
				$oidc->requestUserInfoByID($sub, 'user_metadata.last_name'),
				$oidc->requestUserInfoByID($sub, 'user_metadata.ror_id'),
				$oidc->requestUserInfoByID($sub, 'user_metadata.affiliation'),
				$oidc->requestUserInfoByID($sub, 'user_metadata.organization_country'),
				$oidc->requestUserInfoByID($sub, 'user_metadata.subject_matter_expertise_provider'),
				$oidc->requestUserInfoByID($sub, 'user_metadata.orcid')
			);
		}
		catch (Throwable $ex) {
			if ($isSilentAuth) {
				error_log('Silent OIDC user linking failed: ' . $ex->getMessage());
				neonAuthFinishFlow($returnUrl, true);
			}
			neonAuthClearFlowState();
			$_SESSION['last_message'] = $LANG['CAUGHT_EXCEPTION'] . ' ' . $ex->getMessage();
			header('Location: ' . $CLIENT_ROOT . '/profile/index.php');
			exit;
		}

		if ($linked && $profManager->authenticate($sub, $PROVIDER_URLS[$AUTH_PROVIDER])) {
			$profManager->linkThirdPartySid($sid, session_id(), $_SERVER['REMOTE_ADDR']);
			$localAuthenticated = true;
		}
	}
}

if ($localAuthenticated) {
	neonAuthFinishFlow($returnUrl, $isSilentAuth);
}

if ($isSilentAuth) {
	neonAuthFinishFlow($returnUrl, true);
}

neonAuthClearFlowState();
$_SESSION['last_message'] = ($email ?? false)
	? $LANG['UNKNOWN_ERROR'] . ' <ERR/>'
	: $LANG['UNABLE_RETRIEVE_EMAIL'] . ' <ERR/>';
header('Location: ' . $CLIENT_ROOT . '/profile/index.php');
exit;

