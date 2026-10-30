<?php
/**
 * Code128 with automatic B/C switching for uppercase letters and digits.
 *
 * Adapted from the PEAR Image_Barcode2 Code128 driver.
 * Original author: Jeffrey K. Brown <jkb@darkfantastic.net>
 * Copyright 2005 The PHP Group
 * License: PHP License 3.0 — https://www.php.net/license/3_0.txt
 */

require_once 'Image/Barcode2/Driver.php';
require_once 'Image/Barcode2/Common.php';
require_once 'Image/Barcode2/Exception.php';

class Image_Barcode2_Driver_Code128Auto extends Image_Barcode2_Common implements Image_Barcode2_Driver
{
    private $_patterns = array(
        '212222', '222122', '222221', '121223', '121322',
        '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231',
        '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222',
        '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123',
        '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131',
        '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123',
        '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422',
        '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211',
        '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112',
        '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141',
        '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131',
        '211412', // 103: Start A
        '211214', // 104: Start B
        '211232', // 105: Start C
        '2331112' // 106: Stop
    );

    public function __construct(Image_Barcode2_Writer $writer)
    {
        parent::__construct($writer);
        $this->setBarcodeHeight(50);
        $this->setBarcodeWidth(2);
    }

    public function validate()
    {
        if (!preg_match('/\A[A-Z0-9]{1,100}\z/', $this->getBarcode())) {
            throw new Image_Barcode2_Exception(
                'Barcode must contain 1–100 uppercase letters or digits.'
            );
        }
    }

    private function encode($text)
    {
        // Start in subset B.
        $codes = array(104);
        $length = strlen($text);
        $position = 0;

        while ($position < $length) {
            $digitCount = strspn($text, '0123456789', $position);

            if ($digitCount >= 4) {
                // Encode the first digit in B if the run has odd length.
                if ($digitCount % 2 !== 0) {
                    $codes[] = ord($text[$position]) - 32;
                    $position++;
                    $digitCount--;
                }

                // Switch to C and encode pairs of digits.
                $codes[] = 99;
                $end = $position + $digitCount;

                while ($position < $end) {
                    $codes[] = (int) substr($text, $position, 2);
                    $position += 2;
                }

                // Switch back to B if more text follows.
                if ($position < $length) {
                    $codes[] = 100;
                }
            } else {
                $codes[] = ord($text[$position]) - 32;
                $position++;
            }
        }

        $checksum = $codes[0];

        for ($i = 1, $count = count($codes); $i < $count; $i++) {
            $checksum += $codes[$i] * $i;
        }

        $codes[] = $checksum % 103;
        $codes[] = 106;

        return $codes;
    }

    public function draw()
    {
        $this->validate();

        $text = $this->getBarcode();
        $writer = $this->getWriter();
        $codes = $this->encode($text);

        $bars = '';

        foreach ($codes as $code) {
            $bars .= $this->_patterns[$code];
        }

        // Whole-pixel bars with white margins on both sides.
        $unitWidth = max(1, (int) $this->getBarcodeWidth());
        $quietZone = 10 * $unitWidth;
        $barHeight = max(1, (int) $this->getBarcodeHeight());

        $totalUnits = 0;

        for ($i = 0, $count = strlen($bars); $i < $count; $i++) {
            $totalUnits += (int) $bars[$i];
        }

        $barcodeWidth = ($totalUnits * $unitWidth) + (2 * $quietZone);
        $imageWidth = $barcodeWidth;

        // Same Roboto font path used by your original driver.
        $fontFile = __DIR__ . '/../../../fonts/Roboto-Regular.ttf';
        $fontSize = 12;
        $textGap = 6;
        $bottomMargin = 6;
        $imageHeight = $barHeight + $bottomMargin;

        if ($this->showText) {
            if (
                !function_exists('imagettfbbox') ||
                !function_exists('imagettftext')
            ) {
                throw new Image_Barcode2_Exception(
                    'PHP GD needs FreeType support to render Roboto.'
                );
            }

            if (!is_readable($fontFile)) {
                throw new Image_Barcode2_Exception(
                    'Roboto font file was not found: ' . $fontFile
                );
            }

            $textBox = imagettfbbox($fontSize, 0, $fontFile, $text);

            if ($textBox === false) {
                throw new Image_Barcode2_Exception(
                    'Unable to measure text using the Roboto font.'
                );
            }

            $textLeft = min(
                $textBox[0], $textBox[2], $textBox[4], $textBox[6]
            );
            $textRight = max(
                $textBox[0], $textBox[2], $textBox[4], $textBox[6]
            );
            $textTop = min(
                $textBox[1], $textBox[3], $textBox[5], $textBox[7]
            );
            $textBottom = max(
                $textBox[1], $textBox[3], $textBox[5], $textBox[7]
            );

            $textWidth = $textRight - $textLeft;
            $textHeight = $textBottom - $textTop;

            // Allow room for text without stretching the barcode.
            $imageWidth = max(
                $barcodeWidth,
                $textWidth + (2 * $quietZone)
            );

            $imageHeight += $textGap + $textHeight;
        }

        $image = $writer->imagecreate($imageWidth, $imageHeight);

        $white = $writer->imagecolorallocate($image, 255, 255, 255);
        $black = $writer->imagecolorallocate($image, 0, 0, 0);

        $writer->imagefill($image, 0, 0, $white);

        // Center the barcode if text required a wider image.
        $x = $quietZone + (int) floor(
            ($imageWidth - $barcodeWidth) / 2
        );

        $isBar = true;

        for ($i = 0, $count = strlen($bars); $i < $count; $i++) {
            $width = ((int) $bars[$i]) * $unitWidth;

            if ($isBar) {
                $writer->imagefilledrectangle(
                    $image,
                    $x,
                    0,
                    $x + $width - 1,
                    $barHeight - 1,
                    $black
                );
            }

            $x += $width;
            $isBar = !$isBar;
        }

        if ($this->showText) {
            $textX = (int) round(
                ($imageWidth - $textWidth) / 2 - $textLeft
            );
            $textY = (int) ($barHeight + $textGap - $textTop);

            imagettftext(
                $image,
                $fontSize,
                0,
                $textX,
                $textY,
                $black,
                $fontFile,
                $text
            );
        }

        return $image;
    }
}