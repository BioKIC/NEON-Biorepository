<?php
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['SERVER_NAME'] = 'biorepo.neonscience.org';
include_once(__DIR__ .'/../config/symbini.php');
require_once('classes/OccurrenceSesar.php'); 
require_once('classes/IgsnManager.php');
$guidManager = new OccurrenceSesar();
$igsnManager = new IgsnManager();

//Logging functions
function setupLogFile() {
    $logDir = $GLOBALS['SERVER_ROOT'] . '/content/logs/igsn/';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);

    $logPath = $logDir . 'IGSN_' . date('Y-m-d') . '.log';
    $logFH = fopen($logPath, 'a');
    if (!$logFH) {
        echo "Unable to open log file: $logPath\n";
        return null;
    }
    return $logFH;
}

function logMessage($msg, $logFH = null) {
    $timestamp = date('Y-m-d H:i:s');
    $fullMsg = "[$timestamp] $msg\n";
    if ($logFH) fwrite($logFH, $fullMsg);
    echo $fullMsg;
}

$SYMB_UID = 104; //code will us my (Chandra's) tokens to mint IGSN IDs
$GLOBALS['SYMB_UID'] = $SYMB_UID;

set_time_limit(0);

$logFH = setupLogFile();
if ($guidManager->getProductionMode()) {
    logMessage("Starting auto-batch IGSN processing", $logFH);
} else {
    logMessage("Starting auto-batch IGSN processing (Development Mode)", $logFH);
}

$igsnManager = new IgsnManager();
$taskList = $igsnManager->getIgsnTaskReport();

if ($taskList) {
    foreach ($taskList as $collid => $collArr) {
        $guidManager = new OccurrenceSesar();
        
        $guidManager->setCollid($collid);
        $guidManager->setCollArr();
        $igsnSeed = $guidManager->generateIgsnSeed();
        
		if ($guidManager->getProductionMode()) {
			$accessToken = $guidManager->getAccessToken($SYMB_UID);
            $refreshToken = $guidManager->getRefreshToken($SYMB_UID);
		} else {
            $accessToken = $guidManager->getDevelopmentAccessToken($SYMB_UID);
            $refreshToken = $guidManager->getDevelopmentRefreshToken($SYMB_UID);
        }

        if (!$guidManager->isAccessTokenValid($accessToken)) {
            logMessage("Access token expired for UID $SYMB_UID. Attempting refresh...", $logFH);
            $accessToken = $guidManager->refreshAccessToken($refreshToken, $SYMB_UID);
            if ($accessToken) {
                logMessage("Token refresh successful.", $logFH);
            } else {
                logMessage("Token refresh failed. Aborting batch.", $logFH);
                if ($logFH) fclose($logFH);
                exit(1);
            }
        }

        $guidManager->setIgsnSeed($igsnSeed);
        $guidManager->batchProcessIdentifiers(0);
    //break;
    }
    //transfer over occurrenceIDs to catalognum
    $igsnManager->setNullNeonIdentifiers();
}
logMessage("Finished auto-batch IGSN processing", $logFH);
?>
