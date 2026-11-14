<?php
require_once dirname(__DIR__, 3) . '/config/symbini.php';
require_once $SERVER_ROOT . '/config/dbconnection.php';
require_once $SERVER_ROOT . '/neon/classes/AllSamplesCount.php';
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(array('error' => 'Use POST for sample counts.'));
    exit;
}
$connection = null;
try {
    $body = file_get_contents('php://input', false, null, 0, 1025);
    if (strlen($body) > 1024) throw new InvalidArgumentException('Count request is too large.');
    $input = json_decode($body, true, 4, JSON_THROW_ON_ERROR);
    if (!is_array($input) || array_keys($input) !== array('db')) throw new InvalidArgumentException('Invalid count request.');
    $scope = AllSamplesCount::scope($input['db']);
    $connection = MySQLiConnectionFactory::getCon('readonly');
    if (!$connection) throw new RuntimeException('Database connection is unavailable.');
    $sql = AllSamplesCount::query($connection, $scope);
    // SQL includes both the collection scope and the user's record access permissions.
    $key = 'all-samples-count-v1:' . hash('sha256', $SERVER_ROOT . $sql);
    $cacheEnabled = function_exists('apcu_enabled') && apcu_enabled();
    $found = false;
    $count = $cacheEnabled ? apcu_fetch($key, $found) : null;
    if (!$found) {
        $result = $connection->query($sql);
        if (!$result) throw new RuntimeException('Unable to count samples.');
        $row = $result->fetch_assoc();
        $result->free();
        if (!isset($row['count'])) throw new RuntimeException('Sample count is unavailable.');
        $count = (int) $row['count'];
        if ($cacheEnabled) apcu_store($key, $count, 300);
    }
    echo json_encode(array('count' => $count), JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException | JsonException $error) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid sample count request.'));
} catch (Throwable $error) {
    error_log('All samples count: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(array('error' => 'The sample count is temporarily unavailable.'));
} finally {
    if ($connection) $connection->close();
}
