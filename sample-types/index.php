<?php
require_once dirname(__DIR__) . '/config/symbini.php';
require_once $SERVER_ROOT . '/neon/classes/SampleTypeUrls.php';

$sampleTypeRequestUri = $_SERVER['REQUEST_URI'] ?? '';
$sampleTypeCode = SampleTypeUrls::requestedCode($sampleTypeRequestUri, $CLIENT_ROOT, $_GET);
$sampleTypePath = parse_url($sampleTypeRequestUri, PHP_URL_PATH);
$sampleTypeDirectory = rtrim($CLIENT_ROOT, '/') . '/sample-types/';
if (!$sampleTypeCode && in_array($sampleTypePath, array($sampleTypeDirectory, $sampleTypeDirectory . 'index.php'), true)
    && !isset($_GET['sampletype'])) {
    header('Location: ' . rtrim($CLIENT_ROOT, '/') . '/collections/misc/browsecollprofiles.php', true, 302);
    exit;
}

try {
    $sampleTypeUrls = SampleTypeUrls::shared();
    $sampleTypeId = $sampleTypeUrls->idForCode($sampleTypeCode);
} catch (Throwable $error) {
    error_log('Sample type route: ' . $error->getMessage());
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><title>Sample Type Unavailable</title><p>The sample type is temporarily unavailable. Please try again.</p></html>';
    exit;
}

if (!$sampleTypeId) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><title>Sample Type Not Found</title><h1>Sample Type Not Found</h1><p><a href="'
        . htmlspecialchars(rtrim($CLIENT_ROOT, '/') . '/collections/misc/browsecollprofiles.php', ENT_QUOTES, 'UTF-8')
        . '">Explore sample types</a></p></html>';
    exit;
}

// Render the existing profile with its resolved ID and its existing permission checks.
$_REQUEST['collid'] = $sampleTypeId;
require $SERVER_ROOT . '/collections/misc/neoncollprofiles.php';
