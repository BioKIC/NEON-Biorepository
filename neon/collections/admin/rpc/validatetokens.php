<?php
header('Content-Type: application/json');
include_once('../../../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/OccurrenceSesar.php');
$guidManager = new OccurrenceSesar();

$accessToken = $_POST['accessToken'] ?? '';
$refreshToken = $_POST['refreshToken'] ?? '';

$message = '';

function decodeJwtPayload($jwt) {
	$parts = explode('.', $jwt);
	if (count($parts) < 2) return null;
	$payload = base64_decode(strtr($parts[1], '-_', '+/'));
	return json_decode($payload, true);
}

$accessTokenValid = false;
if ($accessToken) {
	$url = 'https://app.geosamples.org/webservices/credentials_service_v2.php';
	if(!$guidManager->getProductionMode()) $url = 'https://app-sandbox.geosamples.org/webservices/credentials_service_v2.php';

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Authorization: Bearer ' . $accessToken
	]);
	curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($httpCode === 200) {
		$accessTokenValid = true;
	}
}

$message = $accessTokenValid
	? 'Both tokens are valid.'
	: 'Access token is invalid, please refresh';

echo json_encode(['message' => $message]);
