<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');


// Check if session is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if ($data['action'] === 'start_session') {
    $conn = MySQLiConnectionFactory::getCon("write");
    $sql = "SELECT MAX(sessionnum)+1 AS sessionNumber FROM NeonSessioning WHERE sessionUsername = '" . $USERNAME . "';";    
    $rs = $conn->query($sql);
    while($r = $rs->fetch_object()){
        $sessionNum = $r->sessionNumber;
        if (is_null($sessionNum)) {
            $sessionNum = 1;
        }
    }
    $rs->free();
    
    $sql = "INSERT INTO NeonSessioning (sessionUsername, sessionNum, startTime) VALUES ('" . $USERNAME . "', " . $sessionNum . ", '" . date('Y-m-d H:i:s') . "');"; 
    $conn->query($sql);
    $sessionID = $conn->insert_id;
    if($conn) $conn->close();
    
    $session_data = [
        'sessionName' => $USERNAME.'-'.$sessionNum,
        'start_time' => date('Y-m-d H:i:s'),
        'end_time' => null,
        'sessionID' => $sessionID,
    ];

    $_SESSION['sampleCheckinSessionData'] = $session_data;

    // Return session data to frontend
    echo json_encode(['start_time' => $session_data['start_time'],
                      'sessionName' => $session_data['sessionName'],
                      'sessionID' => $session_data['sessionID']]);
}

if ($data['action'] === 'stop_session') {
    $conn = MySQLiConnectionFactory::getCon("write");
    $session_data = $_SESSION['sampleCheckinSessionData'];
    $session_data['end_time'] = date('Y-m-d H:i:s');
    
    $sqlUpdate = "UPDATE NeonSessioning SET endTime = '".$session_data['end_time']."' WHERE sessionID = ". $session_data['sessionID'];
    $conn->query($sqlUpdate);
    if($conn) $conn->close();
    $_SESSION['sampleCheckinSessionData'] = $session_data;

    // Return session end time to frontend
    echo json_encode(['end_time' => $session_data['end_time']]);
}
?>
