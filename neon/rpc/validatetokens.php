<?php
header('Content-Type: application/json');

$accessToken = $_POST['accessToken'] ?? '';
$refreshToken = $_POST['refreshToken'] ?? '';

$message = '';

function decodeJwtPayload($jwt) {
	$parts = explode('.', $jwt);
	if (count($parts) < 2) return null;
	$payload = base64_decode(strtr($parts[1], '-_', '+/'));
	return json_decode($payload, true);
}

//$refreshTokenValid = false;
//if ($refreshToken) {
//	$payload = decodeJwtPayload($refreshToken);
//	if ($payload && isset($payload['exp']) && $payload['exp'] > time()) {
//		$refreshTokenValid = true;
//	}
//}

//if (!$refreshTokenValid) {
//	$message = 'Both tokens are invalid or expired.';
//	echo json_encode(['message' => $message]);
//	exit;
//}

$accessTokenValid = false;
if ($accessToken) {
	$ch = curl_init('https://app.geosamples.org/webservices/credentials_service_v2.php');
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
//	: 'Refresh token is valid, but access token is invalid or expired.';

echo json_encode(['message' => $message]);
