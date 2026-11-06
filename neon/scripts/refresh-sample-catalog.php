<?php
// Run after nightly maintenance: php neon/scripts/refresh-sample-catalog.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Legacy portal configuration expects an HTTP port even when invoked from CLI.
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? 80;
require_once dirname(__DIR__, 2) . '/config/symbini.php';
require_once $SERVER_ROOT . '/neon/classes/SampleCatalog.php';
$directory = $SERVER_ROOT . '/neon/cache';
$lock = fopen($directory . '/sample-catalog.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { fwrite(STDERR, "Catalog refresh already running or cache is not writable\n"); exit(1); }
$temp = null;
try {
    $catalog = (new SampleCatalog())->build();
    $json = json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $temp = tempnam($directory, 'catalog-');
    if (!$temp || file_put_contents($temp, $json) !== strlen($json)) throw new RuntimeException('Cannot write catalog');
    chmod($temp, 0644);
    if (!rename($temp, $directory . '/sample-catalog.json')) throw new RuntimeException('Cannot publish catalog');
    echo count($catalog['items']) . " sample types refreshed\n";
} catch (Throwable $error) {
    if ($temp && is_file($temp)) unlink($temp);
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
