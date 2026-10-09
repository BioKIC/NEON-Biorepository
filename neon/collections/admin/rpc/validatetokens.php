<?php
header('Content-Type: application/json');
include_once('../../../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/OccurrenceSesar.php');
$guidManager = new OccurrenceSesar();

$accessToken = $_POST['accessToken'] ?? '';
$refreshToken = $_POST['refreshToken'] ?? '';

$message = '';

$accessTokenValid = $accessToken
	? $guidManager->isAccessTokenValid($accessToken, null)
	: false;

$message = $accessTokenValid
	? 'Both tokens are valid.'
	: 'Access token is invalid, please refresh';

echo json_encode(['message' => $message]);
