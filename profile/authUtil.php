<?php

/**
 * Return a same-origin URL suitable for an authentication redirect.
 */
function neonAuthGetSafeReturnUrl($candidate, $clientRoot)
{
	$origin = rtrim(GeneralUtil::getDomain(), '/');
	$fallback = $origin . rtrim($clientRoot, '/') . '/index.php';

	if (!is_string($candidate) || $candidate === '' || preg_match('/[\r\n]/', $candidate)) {
		return $fallback;
	}

	$candidate = html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	if (substr($candidate, 0, 1) === '/' && substr($candidate, 0, 2) !== '//') {
		return $origin . $candidate;
	}

	$candidateParts = parse_url($candidate);
	$originParts = parse_url($origin);
	if ($candidateParts === false || $originParts === false || empty($candidateParts['host'])) {
		return $fallback;
	}

	$candidateScheme = strtolower($candidateParts['scheme'] ?? '');
	$candidateHost = strtolower($candidateParts['host']);
	$candidatePort = $candidateParts['port'] ?? null;
	$originScheme = strtolower($originParts['scheme'] ?? '');
	$originHost = strtolower($originParts['host'] ?? '');
	$originPort = $originParts['port'] ?? null;

	if ($candidateScheme !== $originScheme || $candidateHost !== $originHost || $candidatePort !== $originPort) {
		return $fallback;
	}

	return $candidate;
}

/**
 * Remove the local Symbiota and OIDC session state.
 */
function neonAuthClearLocalSession($profileManager = null)
{
	$localSessionId = session_id();
	$thirdPartySid = $_SESSION['AUTH_SID'] ?? '';

	if ($profileManager && method_exists($profileManager, 'removeThirdPartySid') && $localSessionId) {
		$profileManager->removeThirdPartySid($localSessionId, $thirdPartySid);
	}
	if ($profileManager && method_exists($profileManager, 'reset')) {
		$profileManager->reset();
	}

	foreach ([
		'ACCESS_TOKEN',
		'AUTH_CLIENT_ID',
		'AUTH_PROVIDER',
		'AUTH_SID',
		'SUBSCRIBER',
	] as $sessionKey) {
		unset($_SESSION[$sessionKey]);
	}
}

/**
 * Remove the short-lived state used to distinguish silent and interactive flows.
 */
function neonAuthClearFlowState()
{
	unset($_SESSION['NEON_AUTH_FLOW_MODE']);
	unset($_SESSION['NEON_AUTH_FLOW_WAS_AUTHENTICATED']);
}

/**
 * Validate an OIDC error callback and clear the library's one-time state.
 */
function neonAuthValidateAndClearErrorState($requestState)
{
	$expectedState = $_SESSION['openid_connect_state'] ?? '';
	$valid = is_string($requestState)
		&& $requestState !== ''
		&& is_string($expectedState)
		&& $expectedState !== ''
		&& hash_equals($expectedState, $requestState);

	unset($_SESSION['openid_connect_state']);
	unset($_SESSION['openid_connect_nonce']);
	unset($_SESSION['openid_connect_code_verifier']);

	return $valid;
}

/**
 * Complete a hidden-frame session check without exposing tokens or user data.
 */
function neonAuthSendSyncResult($authenticated, $changed)
{
	neonAuthClearFlowState();
	unset($_SESSION['refurl']);
	$_SESSION['NEON_AUTH_LAST_SYNC'] = time();

	$payload = json_encode([
		'type' => 'neon-biorepo-auth-sync',
		'authenticated' => (bool) $authenticated,
		'changed' => (bool) $changed,
	], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

	if (session_status() === PHP_SESSION_ACTIVE) {
		session_write_close();
	}

	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	echo '<!doctype html><html><head><meta charset="utf-8"><title>Authentication check</title></head><body>';
	echo '<script>if(window.parent&&window.parent!==window){window.parent.postMessage(' . $payload . ',window.location.origin);}</script>';
	echo '</body></html>';
	exit;
}

