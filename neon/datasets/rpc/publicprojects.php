<?php
require_once dirname(__DIR__, 3) . '/config/symbini.php';
require_once $SERVER_ROOT . '/config/dbconnection.php';
require_once $SERVER_ROOT . '/neon/classes/PublicProjectSearch.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
// The overview and table requests must not wait on each other's PHP session lock.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(array('error' => 'Use GET to search public projects.'));
    exit;
}
try {
    $search = new PublicProjectSearch();
    $action = $_GET['action'] ?? 'search';
    if ($action === 'overview') {
        $response = $search->overview();
        header('Cache-Control: public, max-age=900');
    } elseif ($action === 'search') {
        $response = $search->search($_GET);
    } else {
        throw new InvalidArgumentException('Unknown public project request.');
    }
    echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    echo json_encode(array('error' => $error->getMessage()));
} catch (Throwable $error) {
    error_log('Public project search failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(array('error' => 'Unable to load projects. Please try again.'));
}
