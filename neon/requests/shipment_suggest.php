<?php
include_once('../../config/symbini.php');

header('Content-Type: application/json');

$term = trim($_GET['term'] ?? '');

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$conn = MySQLiConnectionFactory::getCon('readonly');

$sql = 'SELECT s.id, CONCAT(r.name, " (",s.shipDate,")") as shipment
        FROM neonrequestshipment s
        LEFT JOIN neonresearcher r
        ON s.researcherID = r.researcherID
        WHERE r.name LIKE ?
        ORDER BY CONCAT(r.name, " (",s.shipDate,")")
        LIMIT 20';

$stmt = $conn->prepare($sql);

$like = "%{$term}%";
$stmt->bind_param("s", $like);

$stmt->execute();
$res = $stmt->get_result();

$out = [];

while ($row = $res->fetch_assoc()) {
    $out[] = [
        "label" => $row['shipment'],
        "value" => $row['shipment'], 
        "id" => $row['id'] 
    ];
}

echo json_encode($out);

?>