<?php
// show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// return JSON
header('Content-Type: application/json');

include_once('../../config/symbini.php');
include_once('../../neon/classes/InquiriesManager.php');

try {

    $inquiryManager = new InquiriesManager();

    $collectionManager = trim($_POST['collectionManager'] ?? '');
    $researcherID = trim($_POST['researcherID'] ?? '');
    $inquiryDate = trim($_POST['inquiryDate'] ?? '');


    if (!$researcherID || !$collectionManager || !$inquiryDate) {
        throw new Exception('All fields required');
    }

    $id = $inquiryManager->addInquiry($collectionManager, $researcherID, $inquiryDate);

    if (!$researcherID) {
        throw new Exception('Failed to add inquiry.');
    }

    echo json_encode([
        'success' => true,
        'researcherID' => $researcherID,
        'inquiryDate' => $inquiryDate,
        'collectionManager' => $collectionManager
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
