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

    $name = trim($_POST['name'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $contactEmail = trim($_POST['contactEmail'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$name || !$institution) {
        throw new Exception('Both name and institution are required.');
    }

    if (!method_exists($inquiryManager, 'addResearcher')) {
        throw new Exception('addResearcher method not defined in inquiries manager.');
    }

    $researcherID = $inquiryManager->addResearcher($name, $institution, $contactEmail, $address, $phone);

    if (!$researcherID) {
        throw new Exception('Failed to add researcher.');
    }

    echo json_encode([
        'success' => true,
        'researcherID' => $researcherID,
        'name' => $name,
        'institution' => $institution,
        'contactEmail' => $contactEmail,
        'address' => $address,
        'phone' => $phone
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
