<?php

set_time_limit(0);

$baseUrl = "https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/";
$files = [
    "tiny_face_detector_model-weights_manifest.json",
    "tiny_face_detector_model-shard1",
    "face_landmark_68_model-weights_manifest.json",
    "face_landmark_68_model-shard1",
    "face_recognition_model-weights_manifest.json",
    "face_recognition_model-shard1",
    "face_recognition_model-shard2"
];

$dir = __DIR__ . '/../public/models';

if (!file_exists($dir)) {
    mkdir($dir, 0755, true);
}

echo "Memulai pengunduhan model weights face-api.js...\n";

foreach ($files as $file) {
    $targetPath = "{$dir}/{$file}";
    
    // Skip if already exists
    if (file_exists($targetPath) && filesize($targetPath) > 0) {
        echo "[EXISTS] {$file} sudah ada, melewati...\n";
        continue;
    }
    
    $url = $baseUrl . $file;
    echo "[DOWNLOADING] {$url} -> {$targetPath}...\n";
    
    $ch = curl_init($url);
    $fp = fopen($targetPath, 'wb');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Ignore SSL errors if any
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    curl_exec($ch);
    
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    
    if ($statusCode === 200) {
        echo "[SUCCESS] Berhasil mengunduh {$file} (" . round(filesize($targetPath)/1024, 2) . " KB)\n";
    } else {
        echo "[ERROR] Gagal mengunduh {$file} (HTTP Code: {$statusCode})\n";
        @unlink($targetPath);
    }
}

echo "Proses pengunduhan model weights selesai!\n";
