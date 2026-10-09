<?php
header('Content-Type: application/json');
include_once('../../../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/OccurrenceSesar.php');

$guidManager = new OccurrenceSesar();

$refreshToken = $_POST['refreshToken'] ?? '';

if (!$refreshToken) {
	echo json_encode([
		'success' => false,
		'message' => 'Missing refresh token.'
	]);
	exit;
}

$tokens = $guidManager->refreshAccessToken($refreshToken);

if ($tokens) {
	echo json_encode([
		'success' => true,
		'newAccessToken' => $tokens['access'],
		'newRefreshToken' => $tokens['refresh']
	]);
} else {
	echo json_encode([
		'success' => false,
		'message' => 'Invalid or expired refresh token.'
	]);
}