<?php
header('Content-Type: application/json');

$refreshToken = $_POST['refreshToken'] ?? '';

if (!$refreshToken) {
	echo json_encode([
		'success' => false,
		'message' => 'Missing refresh token.'
	]);
	exit;
}

$url = 'https://app.geosamples.org/webservices/refresh_token.php';
$data = http_build_query(['refresh' => $refreshToken]);

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
		echo json_encode([
			'success' => false,
			'message' => 'Expected tokens not found in response.'
		]);
		exit;
	}
} else {
	echo json_encode([
		'success' => false,
		'message' => 'Token refresh request failed.'
	]);
}
