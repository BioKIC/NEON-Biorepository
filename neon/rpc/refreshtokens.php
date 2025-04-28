<?php
header('Content-Type: application/json');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSesar.php');
$guidManager = new OccurrenceSesar();

$refreshToken = $_POST['refreshToken'] ?? '';

if (!$refreshToken) {
	echo json_encode([
		'success' => false,
		'message' => 'Missing refresh token.'
	]);
	exit;
}

$url = 'https://app.geosamples.org/webservices/refresh_token.php';
if(!$guidManager->getProductionMode()) $url = 'https://app-sandbox.geosamples.org/webservices/refresh_token.php';
$data = http_build_query(['refresh' => $refreshToken]);


if ($guidManager->getProductionMode()) {
    $loginUrl = 'https://app.geosamples.org/';
    $modeText = 'Production';
} else {
    $loginUrl = 'https://app-sandbox.geosamples.org/';
    $modeText = 'Development';
}
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Content-Type: application/x-www-form-urlencoded'
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $result) {
	$response = json_decode($result, true);
	if (isset($response['access']) && isset($response['refresh'])) {
		echo json_encode([
			'success' => true,
			'newAccessToken' => $response['access'],
			'newRefreshToken' => $response['refresh']
		]);
		exit;
	} else {
		$errorMsg = $response['error'] ?? 'Unknown error';
		if (strpos($errorMsg, 'Invalid or expired refresh token.') !== false) {
			$errorMsg .= ' Refresh tokens must be generated through <a href="' . $loginUrl . '" target="_blank">' . htmlspecialchars($loginUrl) . '</a> (' . $modeText . ' server) and entered here manually.';
		}
		echo json_encode([
			'success' => false,
			'message' => $errorMsg
		]);
		exit;
	}
} else {
	$errorMsg = 'Unknown error';
	if (isset($response['error'])) {
		$errorMsg = $response['error'];
		if (strpos($errorMsg, 'Invalid or expired refresh token.') !== false) {
			$errorMsg .= ' Refresh tokens must be generated through <a href="' . $loginUrl . '" target="_blank">' . htmlspecialchars($loginUrl) . '</a> (' . $modeText . ' server) and entered here manually.';
		}
	}
	echo json_encode([
		'success' => false,
		'message' => $errorMsg
	]);
}

