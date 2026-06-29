<?php

// Target sizes for PWA and favicon
$sizes = [
    72 => 'public/images/icons/icon-72x72.png',
    96 => 'public/images/icons/icon-96x96.png',
    128 => 'public/images/icons/icon-128x128.png',
    144 => 'public/images/icons/icon-144x144.png',
    152 => 'public/images/icons/icon-152x152.png',
    192 => 'public/images/icons/icon-192x192.png',
    384 => 'public/images/icons/icon-384x384.png',
    512 => 'public/images/icons/icon-512x512.png',
    32 => 'public/favicon.ico', // Simple 32x32 fallback favicon
];

// Ensure target directory exists
if (!is_dir('public/images/icons')) {
    mkdir('public/images/icons', 0755, true);
}

// Generate the responsive SVG favicon with white rounded-square background
$svg_content = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <rect width="24" height="24" rx="4" fill="#ffffff" />
  <g transform="translate(2.4, 2.4) scale(0.8)" stroke="#4f46e5" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 7v4" />
    <path d="M14 21v-3a2 2 0 0 0-4 0v3" />
    <path d="M14 9h-4" />
    <path d="M18 11h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2" />
    <path d="M18 21V5a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16" />
  </g>
</svg>';

file_put_contents('public/favicon.svg', $svg_content);
echo "Generated public/favicon.svg\n";

// Helper to draw a filled rounded rectangle in GD
function imagefilledroundedrect($img, $x1, $y1, $x2, $y2, $radius, $color) {
    // Draw 4 corner circles
    imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    
    // Draw rectangles to fill the middle
    imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
}

// Function to draw and save the icon at a specific size
function generate_png_icon($size, $dest_path) {
    // 4x supersampling for high-quality anti-aliasing
    $render_size = $size * 4;
    $img = imagecreatetruecolor($render_size, $render_size);
    
    // Save alpha channel (transparent background corners)
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    // Colors based on user feedback
    // Background: Solid White (#ffffff)
    $bg_color = imagecolorallocate($img, 255, 255, 255);
    // Theme color (indigo-blue): #4f46e5 -> RGB(79, 70, 229)
    $stroke_color = imagecolorallocate($img, 79, 70, 229);
    
    // 1. Draw outer rounded square
    $corner_radius = (int)(4 * $render_size / 24);
    imagefilledroundedrect($img, 0, 0, $render_size - 1, $render_size - 1, $corner_radius, $bg_color);
    
    // Thickness of stroke (2 units on a 24x24 grid)
    $thickness = 2 * $render_size / 24;
    imagesetthickness($img, (int)max(1, $thickness));
    
    // Scale helper (scale = 0.8, translation = 2.4 to enlarge the icon)
    $scale = function($coord) use ($render_size) {
        return ($coord * 0.8 + 2.4) * $render_size / 24;
    };
    
    // Helper to draw line with rounded caps
    $drawLine = function($x1, $y1, $x2, $y2) use ($img, $stroke_color, $thickness, $scale) {
        $px1 = $scale($x1);
        $py1 = $scale($y1);
        $px2 = $scale($x2);
        $py2 = $scale($y2);
        
        imageline($img, (int)$px1, (int)$py1, (int)$px2, (int)$py2, $stroke_color);
        // Draw round caps at the endpoints
        imagefilledellipse($img, (int)$px1, (int)$py1, (int)$thickness, (int)$thickness, $stroke_color);
        imagefilledellipse($img, (int)$px2, (int)$py2, (int)$thickness, (int)$thickness, $stroke_color);
    };
    
    // Helper to draw an arc with rounded caps
    $drawArc = function($cx, $cy, $w, $h, $start, $end) use ($img, $stroke_color, $scale, $thickness, $render_size) {
        $pcx = $scale($cx);
        $pcy = $scale($cy);
        $pw = $w * 0.8 * $render_size / 24;
        $ph = $h * 0.8 * $render_size / 24;
        
        imagearc($img, (int)$pcx, (int)$pcy, (int)$pw, (int)$ph, $start, $end, $stroke_color);
        
        // Draw round caps at start and end angles of the arc
        $rad_start = deg2rad($start);
        $rad_end = deg2rad($end);
        
        $x_start = $pcx + ($pw / 2) * cos($rad_start);
        $y_start = $pcy + ($ph / 2) * sin($rad_start);
        $x_end = $pcx + ($pw / 2) * cos($rad_end);
        $y_end = $pcy + ($ph / 2) * sin($rad_end);
        
        imagefilledellipse($img, (int)$x_start, (int)$y_start, (int)$thickness, (int)$thickness, $stroke_color);
        imagefilledellipse($img, (int)$x_end, (int)$y_end, (int)$thickness, (int)$thickness, $stroke_color);
    };
    
    // 2. Draw hospital icon paths
    // M12 7v4
    $drawLine(12, 7, 12, 11);
    
    // M14 9h-4
    $drawLine(10, 9, 14, 9);
    
    // M14 21v-3a2 2 0 0 0-4 0v3
    $drawLine(14, 21, 14, 18);
    $drawArc(12, 18, 4, 4, 180, 360);
    $drawLine(10, 18, 10, 21);
    
    // Right wing, bottom, and left wing outlines
    // M18 11h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2
    $drawLine(18, 11, 20, 11);
    $drawArc(20, 13, 4, 4, 270, 360);
    $drawLine(22, 13, 22, 19);
    $drawArc(20, 19, 4, 4, 0, 90);
    $drawLine(20, 21, 4, 21);
    $drawArc(4, 19, 4, 4, 90, 180);
    $drawLine(2, 19, 2, 10);
    $drawArc(4, 10, 4, 4, 180, 270);
    $drawLine(4, 8, 6, 8);
    
    // Central building outline
    // M18 21V5a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16
    $drawLine(18, 21, 18, 5);
    $drawArc(16, 5, 4, 4, 270, 360);
    $drawLine(16, 3, 8, 3);
    $drawArc(8, 5, 4, 4, 180, 270);
    $drawLine(6, 5, 6, 21);
    
    // Resample down to destination size
    $out = imagecreatetruecolor($size, $size);
    imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    
    imagecopyresampled($out, $img, 0, 0, 0, 0, $size, $size, $render_size, $render_size);
    
    // Save image
    imagepng($out, $dest_path, 9); // max compression
    
    // Free memory
    imagedestroy($img);
    imagedestroy($out);
}

// Generate all sizes
foreach ($sizes as $size => $dest_path) {
    generate_png_icon($size, $dest_path);
    echo "Generated $dest_path ({$size}x{$size})\n";
}

echo "All icons generated successfully!\n";
