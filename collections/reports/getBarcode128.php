<?php
ini_set('display_errors', '0');

include_once __DIR__ . '/../../config/symbini.php';

function barcode128Error($status, $message)
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

$bcText = isset($_GET['bctext']) ? $_GET['bctext'] : '';

if (
    !is_string($bcText) ||
    !preg_match('/\A[A-Z0-9]{1,100}\z/', $bcText)
) {
    barcode128Error(
        400,
        'Barcode must contain 1–100 uppercase letters or digits.'
    );
}

$bcHeight = isset($_GET['bcheight']) ? $_GET['bcheight'] : 50;

if (
    !is_scalar($bcHeight) ||
    filter_var($bcHeight, FILTER_VALIDATE_INT) === false ||
    (int) $bcHeight < 10 ||
    (int) $bcHeight > 500
) {
    barcode128Error(400, 'Barcode height must be an integer from 10 to 500.');
}

$bcHeight = (int) $bcHeight;
$pearRoot = realpath(__DIR__ . '/../../neon/pear');

if (
    $pearRoot === false ||
    !is_file($pearRoot . '/Image/Barcode2.php') ||
    !is_file($pearRoot . '/Image/Barcode2/Driver/Code128Auto.php')
) {
    barcode128Error(
        500,
        'Barcode library or Code128Auto driver was not found.'
    );
}

set_include_path($pearRoot . PATH_SEPARATOR . get_include_path());

try {
    require_once $pearRoot . '/Image/Barcode2.php';

    $barcode = new Image_Barcode2();

    $image = $barcode->draw(
        $bcText,
        'Code128Auto',
        'png',
        false,
        $bcHeight
    );

    if (!$image) {
        throw new Exception('Barcode generation returned no image.');
    }

    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
} catch (Exception $e) {
    error_log('Code128 generation failed: ' . $e->getMessage());
    barcode128Error(500, 'Unable to generate barcode. Check the PHP error log.');
}