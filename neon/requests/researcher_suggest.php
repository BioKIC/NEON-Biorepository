<?php
include_once('../../config/symbini.php');

header('Content-Type: application/json');

$term = trim($_GET['term'] ?? '');

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$conn = MySQLiConnectionFactory::getCon('readonly');

$sql = "SELECT researcherID, CONCAT(name,' (', institution, ')') AS researcher
        FROM neonresearcher
        WHERE name LIKE ? OR institution LIKE ?
        ORDER BY name
        LIMIT 20";

$stmt = $conn->prepare($sql);

$like = "%{$term}%";
$stmt->bind_param("ss", $like, $like);

$stmt->execute();
$res = $stmt->get_result();

$out = [];

while ($row = $res->fetch_assoc()) {
    $out[] = [
        "label" => $row['researcher'],
        "value" => $row['researcher'], 
        "resid" => $row['researcherID'] 
    ];
}

echo json_encode($out);

?>