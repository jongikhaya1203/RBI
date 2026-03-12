<?php
/**
 * PWA Icon Generator - RBI Engineering Suite
 * Generates SVG-based PNG icons at all required sizes
 * Uses GD library to create professional shield+gear icons
 *
 * Usage: php generate-icons.php
 * Or visit: /rbi/static/icons/generate-icons.php
 */

// Icon sizes required for PWA
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$outputDir = __DIR__;

// Colors
$bgColor = [59, 130, 246];    // #3b82f6 (blue)
$iconColor = [255, 255, 255]; // white
$darkBg = [15, 23, 42];       // #0f172a

$generated = [];
$errors = [];

foreach ($sizes as $size) {
    $result = generateIcon($size, $outputDir, $bgColor, $iconColor);
    if ($result) {
        $generated[] = "icon-{$size}x{$size}.png";
    } else {
        $errors[] = "icon-{$size}x{$size}.png";
    }
}

// Generate apple-touch-icon (180x180)
$result = generateIcon(180, $outputDir, $bgColor, $iconColor, 'apple-touch-icon.png');
if ($result) {
    $generated[] = 'apple-touch-icon.png';
} else {
    $errors[] = 'apple-touch-icon.png';
}

// Generate favicon (32x32)
$result = generateIcon(32, $outputDir, $bgColor, $iconColor, 'favicon.png');
if ($result) {
    $generated[] = 'favicon.png';
    // Create ICO from 32x32 PNG
    if (createFavicon($outputDir)) {
        $generated[] = 'favicon.ico';
    }
} else {
    $errors[] = 'favicon.png';
}

// Output results
if (php_sapi_name() === 'cli') {
    echo "=== RBI PWA Icon Generator ===\n\n";
    echo "Generated:\n";
    foreach ($generated as $f) echo "  + {$f}\n";
    if ($errors) {
        echo "\nFailed:\n";
        foreach ($errors as $f) echo "  ! {$f}\n";
    }
    echo "\nDone. " . count($generated) . " icons generated.\n";
} else {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><title>Icon Generator</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '</head><body class="p-4"><div class="container">';
    echo '<h2>RBI PWA Icon Generator</h2><hr>';

    if ($generated) {
        echo '<h5 class="text-success">Generated Icons:</h5><div class="row g-3">';
        foreach ($generated as $f) {
            $path = "/rbi/static/icons/{$f}";
            echo "<div class='col-auto text-center'>";
            echo "<img src='{$path}' style='max-width:96px;max-height:96px;border:1px solid #ddd;border-radius:8px;' class='mb-1'><br>";
            echo "<small class='text-muted'>{$f}</small></div>";
        }
        echo '</div>';
    }

    if ($errors) {
        echo '<h5 class="text-danger mt-4">Failed:</h5><ul>';
        foreach ($errors as $f) echo "<li>{$f}</li>";
        echo '</ul>';
    }

    echo '<p class="mt-3 text-muted">' . count($generated) . ' icons generated.</p>';
    echo '</div></body></html>';
}

/**
 * Generate a single icon at the specified size
 */
function generateIcon($size, $dir, $bg, $fg, $filename = null) {
    if (!extension_loaded('gd')) {
        // Fallback: create SVG-based placeholder
        return generateSVGFallback($size, $dir, $bg, $fg, $filename);
    }

    $img = imagecreatetruecolor($size, $size);

    // Enable alpha blending
    imagealphablending($img, true);
    imagesavealpha($img, true);

    // Background color
    $bgCol = imagecolorallocate($img, $bg[0], $bg[1], $bg[2]);
    imagefill($img, 0, 0, $bgCol);

    // Draw rounded rectangle background
    $cornerRadius = (int)($size * 0.18);
    drawRoundedRect($img, 0, 0, $size - 1, $size - 1, $cornerRadius, $bgCol);

    // Icon color
    $fgCol = imagecolorallocate($img, $fg[0], $fg[1], $fg[2]);
    $fgColAlpha = imagecolorallocatealpha($img, $fg[0], $fg[1], $fg[2], 30);

    // Draw shield + gear icon
    $cx = $size / 2;
    $cy = $size / 2;
    $iconSize = $size * 0.55;

    // Shield outline
    drawShield($img, $cx, $cy - $iconSize * 0.05, $iconSize, $fgCol);

    // Gear in center of shield
    $gearSize = $iconSize * 0.35;
    drawGear($img, $cx, $cy + $iconSize * 0.05, $gearSize, $bgCol, $fgCol);

    // Save
    $fname = $filename ?? "icon-{$size}x{$size}.png";
    $path = $dir . DIRECTORY_SEPARATOR . $fname;
    $result = imagepng($img, $path, 9);
    imagedestroy($img);

    return $result;
}

/**
 * Draw a shield shape
 */
function drawShield($img, $cx, $cy, $size, $color) {
    $halfW = $size * 0.45;
    $topY = $cy - $size * 0.45;
    $midY = $cy + $size * 0.1;
    $bottomY = $cy + $size * 0.45;

    // Shield as filled polygon
    $points = [
        $cx - $halfW, $topY,
        $cx + $halfW, $topY,
        $cx + $halfW, $midY,
        $cx, $bottomY,
        $cx - $halfW, $midY
    ];

    imagefilledpolygon($img, $points, $color);

    // Inner border (slightly smaller, using background to create outline effect)
    $innerW = $halfW - max(2, $size * 0.04);
    $innerTopY = $topY + max(2, $size * 0.04);
    $innerMidY = $midY - max(1, $size * 0.02);
    $innerBottomY = $bottomY - max(2, $size * 0.04);

    // Get background color from pixel
    $bgColorIdx = imagecolorat($img, 0, 0);
    $bgRGB = imagecolorsforindex($img, $bgColorIdx);
    $bgCol = imagecolorallocate($img, $bgRGB['red'], $bgRGB['green'], $bgRGB['blue']);

    $innerPoints = [
        $cx - $innerW, $innerTopY,
        $cx + $innerW, $innerTopY,
        $cx + $innerW, $innerMidY,
        $cx, $innerBottomY,
        $cx - $innerW, $innerMidY
    ];

    imagefilledpolygon($img, $innerPoints, $bgCol);

    // Re-draw slightly smaller shield in white for the border effect
    $borderW = $halfW - max(3, $size * 0.06);
    $borderTopY = $topY + max(3, $size * 0.06);
    $borderMidY = $midY - max(2, $size * 0.03);
    $borderBottomY = $bottomY - max(3, $size * 0.06);

    $borderPoints = [
        $cx - $borderW, $borderTopY,
        $cx + $borderW, $borderTopY,
        $cx + $borderW, $borderMidY,
        $cx, $borderBottomY,
        $cx - $borderW, $borderMidY
    ];

    // Fill with semi-transparent white
    $semiWhite = imagecolorallocatealpha($img, 255, 255, 255, 100);
    imagefilledpolygon($img, $borderPoints, $semiWhite);
}

/**
 * Draw a simple gear/cog shape
 */
function drawGear($img, $cx, $cy, $radius, $fillColor, $outlineColor) {
    $teeth = 8;
    $innerR = $radius * 0.6;
    $outerR = $radius;

    // Draw gear teeth as thick circle + rectangles
    imagefilledellipse($img, (int)$cx, (int)$cy, (int)($outerR * 2), (int)($outerR * 2), $outlineColor);

    // Draw teeth
    for ($i = 0; $i < $teeth; $i++) {
        $angle = (2 * M_PI / $teeth) * $i;
        $toothLen = $radius * 0.3;
        $toothW = $radius * 0.25;

        $tx = $cx + cos($angle) * ($outerR - 2);
        $ty = $cy + sin($angle) * ($outerR - 2);

        $perpAngle = $angle + M_PI / 2;
        $hw = $toothW / 2;

        $points = [
            (int)($tx + cos($perpAngle) * $hw), (int)($ty + sin($perpAngle) * $hw),
            (int)($tx - cos($perpAngle) * $hw), (int)($ty - sin($perpAngle) * $hw),
            (int)($tx - cos($perpAngle) * $hw + cos($angle) * $toothLen), (int)($ty - sin($perpAngle) * $hw + sin($angle) * $toothLen),
            (int)($tx + cos($perpAngle) * $hw + cos($angle) * $toothLen), (int)($ty + sin($perpAngle) * $hw + sin($angle) * $toothLen),
        ];

        imagefilledpolygon($img, $points, $outlineColor);
    }

    // Inner circle (cutout)
    $holeR = $innerR * 0.5;
    imagefilledellipse($img, (int)$cx, (int)$cy, (int)($holeR * 2), (int)($holeR * 2), $fillColor);
}

/**
 * Draw rounded rectangle
 */
function drawRoundedRect($img, $x1, $y1, $x2, $y2, $radius, $color) {
    imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
}

/**
 * SVG fallback when GD is not available
 */
function generateSVGFallback($size, $dir, $bg, $fg, $filename = null) {
    $bgHex = sprintf('#%02x%02x%02x', $bg[0], $bg[1], $bg[2]);
    $fgHex = sprintf('#%02x%02x%02x', $fg[0], $fg[1], $fg[2]);
    $r = $size * 0.18;

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
  <rect width="{$size}" height="{$size}" rx="{$r}" fill="{$bgHex}"/>
  <g transform="translate({$size}/2, {$size}/2) scale(0.45)">
    <path d="M-40,-50 L40,-50 L40,10 L0,50 L-40,10 Z" fill="none" stroke="{$fgHex}" stroke-width="4"/>
    <circle cx="0" cy="0" r="12" fill="none" stroke="{$fgHex}" stroke-width="3"/>
    <g fill="{$fgHex}">
      <rect x="-2" y="-22" width="4" height="8" rx="1"/>
      <rect x="-2" y="14" width="4" height="8" rx="1"/>
      <rect x="-22" y="-2" width="8" height="4" rx="1"/>
      <rect x="14" y="-2" width="8" height="4" rx="1"/>
    </g>
  </g>
</svg>
SVG;

    $fname = $filename ?? "icon-{$size}x{$size}.svg";
    // Save as SVG since GD not available
    $path = $dir . DIRECTORY_SEPARATOR . $fname;

    // Try to convert SVG to PNG if Imagick is available
    if (extension_loaded('imagick')) {
        $imagick = new Imagick();
        $imagick->readImageBlob($svg);
        $imagick->setImageFormat('png');
        $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);

        $pngName = str_replace('.svg', '.png', $fname);
        if (!str_ends_with($pngName, '.png')) $pngName .= '.png';
        $imagick->writeImage($dir . DIRECTORY_SEPARATOR . $pngName);
        $imagick->destroy();
        return true;
    }

    // Save SVG as fallback, also create a simple PNG with GD-like approach
    file_put_contents($path, $svg);

    // Also save a simple solid-color PNG as the icon
    $pngName = $filename ?? "icon-{$size}x{$size}.png";
    if (!str_ends_with($pngName, '.png')) return true;

    return createSimplePNG($size, $dir . DIRECTORY_SEPARATOR . $pngName, $bg, $fg);
}

/**
 * Create a simple PNG icon without GD (writes raw PNG data)
 */
function createSimplePNG($size, $path, $bg, $fg) {
    // If GD is truly not available, just create a placeholder file
    // noting that icons should be created manually
    if (!extension_loaded('gd')) {
        file_put_contents($path . '.txt', "Please generate icon manually: {$size}x{$size}");
        return false;
    }
    return false;
}

/**
 * Create favicon.ico from existing PNG
 */
function createFavicon($dir) {
    $pngPath = $dir . DIRECTORY_SEPARATOR . 'favicon.png';
    $icoPath = $dir . DIRECTORY_SEPARATOR . 'favicon.ico';

    if (!file_exists($pngPath)) return false;

    // Simple ICO file creation from 32x32 PNG
    $pngData = file_get_contents($pngPath);
    if (!$pngData) return false;

    // ICO header
    $ico = pack('vvv', 0, 1, 1); // Reserved, Type (ICO), Count

    // ICO directory entry
    $ico .= pack('CCCCvvVV',
        32, 32,     // Width, Height
        0,          // Color palette
        0,          // Reserved
        1,          // Color planes
        32,         // Bits per pixel
        strlen($pngData), // Size of image data
        22          // Offset to image data (6 + 16 = 22)
    );

    // Image data (PNG)
    $ico .= $pngData;

    return file_put_contents($icoPath, $ico) !== false;
}
