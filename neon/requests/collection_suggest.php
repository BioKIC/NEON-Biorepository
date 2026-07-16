<?php
include_once('../../config/symbini.php');

header('Content-Type: application/json');

$term = trim($_GET['term'] ?? '');

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$conn = MySQLiConnectionFactory::getCon('readonly');

$sql = "SELECT collid, collectionName
        FROM omcollections
        WHERE collectionName LIKE ?
        ORDER BY collectionName
        LIMIT 20";

$stmt = $conn->prepare($sql);

$like = "%{$term}%";
$stmt->bind_param("s", $like);

$stmt->execute();
$res = $stmt->get_result();

$out = [];

while ($row = $res->fetch_assoc()) {
    $out[] = [
        "label" => $row['collectionName'],
        "value" => $row['collectionName'], 
        "collid" => $row['collid'] 
    ];
}

echo json_encode($out);

?>