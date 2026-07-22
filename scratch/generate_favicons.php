<?php

$src_file = 'public/images/logo-sidebar.png';
if (!file_exists($src_file)) {
    die("Source file not found\n");
}

$src = imagecreatefrompng($src_file);
$sw = imagesx($src);
$sh = imagesy($src);

$targets = [
    'public/favicon-16x16.png' => 16,
    'public/favicon-32x32.png' => 32,
    'public/favicon.ico' => 48,
    'public/apple-touch-icon.png' => 180,
    'public/android-chrome-192x192.png' => 192,
    'public/android-chrome-512x512.png' => 512,

    'public/favicon/favicon-16x16.png' => 16,
    'public/favicon/favicon-32x32.png' => 32,
    'public/favicon/favicon.ico' => 48,
    'public/favicon/apple-touch-icon.png' => 180,
    'public/favicon/android-chrome-192x192.png' => 192,
    'public/favicon/android-chrome-512x512.png' => 512,

    'public/images/icons/icon-72x72.png' => 72,
    'public/images/icons/icon-96x96.png' => 96,
    'public/images/icons/icon-128x128.png' => 128,
    'public/images/icons/icon-144x144.png' => 144,
    'public/images/icons/icon-152x152.png' => 152,
    'public/images/icons/icon-192x192.png' => 192,
    'public/images/icons/icon-384x384.png' => 384,
    'public/images/icons/icon-512x512.png' => 512,
];

if (!is_dir('public/images/icons')) {
    mkdir('public/images/icons', 0755, true);
}
if (!is_dir('public/favicon')) {
    mkdir('public/favicon', 0755, true);
}

foreach ($targets as $path => $size) {
    $canvas = imagecreatetruecolor($size, $size);

    // Solid White background (square, no rounded corners)
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);

    // Padding inside white card (e.g. 8% padding so logo doesn't touch outer edge)
    $pad = (int)round($size * 0.08);
    $target_w = $size - ($pad * 2);
    $target_h = $size - ($pad * 2);

    imagecopyresampled($canvas, $src, $pad, $pad, 0, 0, $target_w, $target_h, $sw, $sh);
    imagepng($canvas, $path, 9);
    imagedestroy($canvas);
    echo "Generated $path ($size x $size)\n";
}

// Generate favicon.svg with solid square white background & logo
$svg_content = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
  <rect width="32" height="32" fill="#ffffff" />
  <image href="/images/logo-sidebar.png" x="2" y="2" width="28" height="28" />
</svg>';
file_put_contents('public/favicon.svg', $svg_content);
file_put_contents('public/favicon/favicon.svg', $svg_content);
echo "Generated public/favicon.svg and public/favicon/favicon.svg\n";

imagedestroy($src);
echo "All square icons generated successfully!\n";
