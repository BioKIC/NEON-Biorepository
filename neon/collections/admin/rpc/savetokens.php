<?php
include_once('../../../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
$conn = MySQLiConnectionFactory::getCon("write");
header('Content-Type: application/json');

include_once($SERVER_ROOT.'/neon/classes/OccurrenceSesar.php');
$guidManager = new OccurrenceSesar();

$accessToken = $_POST['accessToken'] ?? '';
$refreshToken = $_POST['refreshToken'] ?? '';
$uid = $SYMB_UID ?? null;

if (!$uid) {
	echo json_encode(['success' => false, 'message' => 'User ID not provided.']);
	exit;
}

$accessToken = trim($accessToken);
$refreshToken = trim($refreshToken);
$uid = intval($uid);

if (!$guidManager->getProductionMode()) {
    $accessField = 'developmentaccessTokenSesar';
    $refreshField = 'developmentrefreshTokenSesar';
} else {
    $accessField = 'accessTokenSesar';
    $refreshField = 'refreshTokenSesar';
}

$sql = "UPDATE users SET {$accessField} = ?, {$refreshField} = ? WHERE uid = ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
	$stmt->bind_param("ssi", $accessToken, $refreshToken, $uid);
	$success = $stmt->execute();
	$stmt->close();

	echo json_encode([
		'success' => $success,
		'message' => $success ? 'Tokens saved.' : 'Database update failed.'
	]);
} else {
	echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
