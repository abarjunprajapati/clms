<?php
/**
 * Captcha Generation API (SVG Version)
 * Generates a secure alphanumeric captcha as an SVG image.
 */
require_once __DIR__ . '/../include/session.php';
ob_clean();

// Security headers
header('Content-Type: image/svg+xml');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Generate random code
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
$length = 6;
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[rand(0, strlen($chars) - 1)];
}

// Store in session
$_SESSION['captcha'] = $code;

// SVG Parameters
$width = 140;
$height = 45;

// Generate noise lines
$lines = '';
for ($i = 0; $i < 5; $i++) {
    $x1 = rand(0, $width);
    $y1 = rand(0, $height);
    $x2 = rand(0, $width);
    $y2 = rand(0, $height);
    $lines .= "<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='#cbd5e1' stroke-width='1' />";
}

// Generate characters with slight rotation and offset
$text_elements = '';
$step = $width / ($length + 1);
for ($i = 0; $i < $length; $i++) {
    $x = ($i + 1) * $step + rand(-2, 2);
    $y = $height / 2 + 5 + rand(-3, 3);
    $rotate = rand(-15, 15);
    $char = $code[$i];
    $text_elements .= "<text x='$x' y='$y' font-family='Arial, sans-serif' font-weight='bold' font-size='20' fill='#0f2447' text-anchor='middle' transform='rotate($rotate $x $y)'>$char</text>";
}

// Output SVG
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg width="<?php echo $width; ?>" height="<?php echo $height; ?>" viewBox="0 0 <?php echo $width; ?> <?php echo $height; ?>" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="#f8fafc" />
    <?php echo $lines; ?>
    <?php echo $text_elements; ?>
    <!-- Random dots for noise -->
    <?php for ($i = 0; $i < 30; $i++): ?>
        <circle cx="<?php echo rand(0, $width); ?>" cy="<?php echo rand(0, $height); ?>" r="1" fill="#94a3b8" opacity="0.5" />
    <?php endfor; ?>
</svg>

