<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');


include_once('../../config/symbini.php');
include_once('../../neon/classes/InquiriesManager.php');

try {

    $inquiryManager = new InquiriesManager();

    $researcherID = trim($_POST['researcherid'] ?? '');
    $shipDate     = trim($_POST['shipdate'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $shippedBy    = trim($_POST['shippedby'] ?? '');

    if (!$researcherID || !$shipDate || !$address || !$shippedBy) {
        throw new Exception('All fields are required.');
    }

    $shipmentID = $inquiryManager->addShipment($researcherID, $shipDate, $address, $shippedBy);

    if (!$shipmentID) {
        throw new Exception($inquiryManager->errorMessage ?: 'Failed to add shipment.');
    }

    echo json_encode([
        'success'      => true,
        'shipmentid'  => $shipmentID,
        'researcherid'=> $researcherID,
        'shipdate'    => $shipDate,
        'address'      => $address,
        'shippedby'   => $shippedBy
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
