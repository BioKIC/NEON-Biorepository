<?php

include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/neon/classes/InquiriesManager.php');

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists($SERVER_ROOT . '/neon/classes/InquiriesManager.php')) {
    die(json_encode(['error'=>'InquiriesManager.php not found']));
}


global $SYMB_UID;

if (empty($SYMB_UID)) {
    ob_end_clean();
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$requestID = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'check';

if (!$requestID) {
    ob_end_clean();
    echo json_encode(['error' => 'Invalid request ID']);
    exit;
}

$inquiryManager = new InquiriesManager();

if ($action === 'check') {

echo $SYMB_UID;
    $result = $inquiryManager->getDatasetID($requestID);

    ob_end_clean(); 
    if (!empty($result) && isset($result['datasetID'])) {
        echo json_encode(['exists' => true, 'datasetid' => (int)$result['datasetID']]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit;
}

if ($action === 'create') {
    $datasetID = $inquiryManager->createDatasetFromRequest($requestID, $SYMB_UID);

    ob_end_clean(); 
    if (!$datasetID) {
        echo json_encode(['error' => $inquiryManager->getErrorMessage()]);
        exit;
    }

    echo json_encode(['success' => true, 'datasetid' => $datasetID]);
    exit;
}

ob_end_clean();
echo json_encode(['error' => 'Unknown action']);
exit;