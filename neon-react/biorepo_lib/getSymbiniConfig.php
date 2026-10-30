<?php
// Include the symbini.php file
include_once('../../config/symbini.php');

// Collect all variables defined in symbini.php
//$symbiniConfig = get_defined_vars();
$symbiniConfig = [
    'CLIENT_ROOT' => $CLIENT_ROOT,
    'SERVER_HOST' => $SERVER_HOST,
];

// Convert the configuration variables to JSON and return them
header('Content-Type: application/json');
echo json_encode($symbiniConfig);
?>
