<?php

include_once(__DIR__ . '/../../../config/symbini.php');
include_once($SERVER_ROOT . '/config/dbconnection.php');
require_once($SERVER_ROOT . '/neon/classes/OccurrenceLinker.php');

$conn = MySQLiConnectionFactory::getCon("write");

// --------------------------------------------------
// Logging
// --------------------------------------------------

function setupLogFile() {
    $logDir = $GLOBALS['SERVER_ROOT'] . '/content/logs/linkages/';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $logPath = $logDir . 'ExtendedSpecimenLinks_' . date('Y-m-d') . '.log';
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

    if (PHP_SAPI === 'cli') {
        echo $fullMsg;
    } else {
        echo htmlspecialchars("[$timestamp] $msg") . "<br>\n";
    }
}

// --------------------------------------------------
// Run linker
// --------------------------------------------------

$logFH = setupLogFile();

logMessage('Starting extended specimen link processing', $logFH);

try {
    $protocolJsonPath = $GLOBALS['SERVER_ROOT'] . '/neon-react/biorepo_lib/collections-protocol.json';

    $linker = new OccurrenceLinker($conn, $protocolJsonPath, function ($message) use ($logFH) {
        logMessage($message, $logFH);
    });

    $linker->run();

} catch (Throwable $e) {
    logMessage('ERROR: ' . $e->getMessage(), $logFH);

    if ($logFH) fclose($logFH);
    if ($conn) $conn->close();

    exit(1);
}

logMessage('Finished creating extended specimen links', $logFH);

if ($logFH) fclose($logFH);
if ($conn) $conn->close();

exit(0);
?>