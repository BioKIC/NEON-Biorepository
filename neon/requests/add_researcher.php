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

    $name = trim($_POST['name'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$name || !$institution) {
        throw new Exception('Both name and institution are required.');
    }

    if (!method_exists($inquiryManager, 'addResearcher')) {
        throw new Exception('addResearcher method not defined in inquiries manager.');
    }

    $researcher_id = $inquiryManager->addResearcher($name, $institution, $contact_email, $address, $phone);

    if (!$researcher_id) {
        throw new Exception('Failed to add researcher.');
    }

    echo json_encode([
        'success' => true,
        'researcher_id' => $researcher_id,
        'name' => $name,
        'institution' => $institution,
        'contact_email' => $contact_email,
        'address' => $address,
        'phone' => $phone
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
