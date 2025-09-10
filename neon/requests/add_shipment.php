<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');


include_once('../../config/symbini.php');
include_once('../../neon/requests/list/InquiriesManager.php');

try {
    if (!class_exists('InquiriesManager')) {
        throw new Exception('InquiriesManager class not found.');
    }

    $inquiryManager = new InquiriesManager();

    $researcher_id = trim($_POST['researcher_id'] ?? '');
    $ship_date     = trim($_POST['ship_date'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $shipped_by    = trim($_POST['shipped_by'] ?? '');

    if (!$researcher_id || !$ship_date || !$address || !$shipped_by) {
        throw new Exception('All fields are required.');
    }

    $shipment_id = $inquiryManager->addShipment($researcher_id, $ship_date, $address, $shipped_by);

    if (!$shipment_id) {
        throw new Exception($inquiryManager->errorMessage ?: 'Failed to add shipment.');
    }

    echo json_encode([
        'success'      => true,
        'shipment_id'  => $shipment_id,
        'researcher_id'=> $researcher_id,
        'ship_date'    => $ship_date,
        'address'      => $address,
        'shipped_by'   => $shipped_by
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
