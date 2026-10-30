<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');


include_once('../../config/symbini.php');
include_once('../../neon/classes/InquiriesManager.php');

try {

    $inquiryManager = new InquiriesManager();

    
    $requestID = (int)($_POST['requestID'] ?? 0);
    $researcherID = trim($_POST['researcherid'] ?? '');
    $shipDate     = trim($_POST['shipdate'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $shippedBy    = trim($_POST['shippedby'] ?? '');

    if ($requestID <= 0) {
        throw new Exception('Invalid or missing request ID.');
    }

    if (!$researcherID || !$shipDate || !$address || !$shippedBy) {
        throw new Exception('All fields are required.');
    }

    $date = DateTime::createFromFormat('!Y-m-d', $shipDate);

    if (!$date || $date->format('Y-m-d') !== $shipDate) {
        throw new Exception('Invalid shipment date.');
    }

    $shipmentID = $inquiryManager->addShipment(
        $researcherID,
        $shipDate,
        $address,
        $shippedBy
    );

    if (!$shipmentID) {
        throw new Exception(
            $inquiryManager->errorMessage ?: 'Failed to add shipment.'
        );
    }

    if (!$inquiryManager->linkNewShipment($requestID, $shipmentID)) {
        throw new Exception(
            $inquiryManager->errorMessage ?: 'Failed to link shipment to request.'
        );
    }

    echo json_encode([
        'success'      => true,
        'shipmentid'  => $shipmentID,
        'requestID'   => $requestID,
        'researcherid'=> $researcherID,
        'shipdate'    => $shipDate,
        'address'      => $address,
        'shippedby'   => $shippedBy
    ]);

} catch (Throwable $e) {
    error_log('add_shipment.php error: ' . $e->getMessage());

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to save shipment. Please check the fields and try again.'
    ]);
}
