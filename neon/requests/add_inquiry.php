<?php
// show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// return JSON
header('Content-Type: application/json');

include_once('../../config/symbini.php');
include_once('../../neon/requests/list/InquiriesManager.php');

try {
    if (!class_exists('InquiriesManager')) {
        throw new Exception('InquiriesManager class not found.');
    }

    $inquiryManager = new InquiriesManager();

    $collection_manager = trim($_POST['collection_manager'] ?? '');
    $researcher_id = trim($_POST['researcher_id'] ?? '');
    $inquiry_date = trim($_POST['inquiry_date'] ?? '');


    if (!$researcher_id || !$collection_manager || !$inquiry_date) {
        throw new Exception('All fields required');
    }

    $id = $inquiryManager->addInquiry($collection_manager, $researcher_id, $inquiry_date);

    if (!$researcher_id) {
        throw new Exception('Failed to add inquiry.');
    }

    echo json_encode([
        'success' => true,
        'researcher_id' => $researcher_id,
        'inquiry_date' => $inquiry_date,
        'collection_manager' => $collection_manager
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
