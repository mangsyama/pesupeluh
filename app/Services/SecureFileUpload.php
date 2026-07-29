<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class SecureFileUpload
{
    /**
     * Whitelist of allowed MIME types and their corresponding safe extensions.
     */
    private static array $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'video/webm' => 'webm',
    ];

    /**
     * Maximum allowed size for Base64 attachments (default 10 MB).
     */
    private const MAX_FILE_SIZE_BYTES = 10485760;

    /**
     * Safely process and store a Base64 encoded file string.
     *
     * @param string $dataUrl Base64 encoded data URI (e.g., "data:image/png;base64,...")
     * @param string $folder Storage folder under public disk (e.g., "ticket_attachments", "profile_photos")
     * @param string $prefix File name prefix (e.g., "ticket_", "profile_")
     * @return string|null Public storage URL path (e.g., "/storage/ticket_attachments/ticket_xyz.jpg") or null on failure
     */
    public static function saveBase64(string $dataUrl, string $folder = 'ticket_attachments', string $prefix = 'file_'): ?string
    {
        if (empty($dataUrl) || !is_string($dataUrl)) {
            return null;
        }

        // Must contain base64 separator
        if (!str_contains($dataUrl, ';base64,')) {
            return null;
        }

        $parts = explode(';base64,', $dataUrl);
        if (count($parts) !== 2) {
            return null;
        }

        $binaryData = base64_decode($parts[1], true);
        if ($binaryData === false) {
            return null; // Invalid base64
        }

        // Enforce maximum size (10 MB)
        if (strlen($binaryData) > self::MAX_FILE_SIZE_BYTES) {
            return null;
        }

        // Inspect real MIME type from binary contents using finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->buffer($binaryData);

        if (!$detectedMime || !isset(self::$allowedMimeTypes[$detectedMime])) {
            return null; // Disallowed MIME type (e.g. PHP, SVG, HTML)
        }

        $extension = self::$allowedMimeTypes[$detectedMime];
        $filename = $prefix . Str::random(20) . '_' . time() . '.' . $extension;

        Storage::disk('public')->makeDirectory($folder);
        $stored = Storage::disk('public')->put($folder . '/' . $filename, $binaryData);

        if (!$stored) {
            return null;
        }

        return '/storage/' . $folder . '/' . $filename;
    }

    /**
     * Safely process and store an UploadedFile object.
     */
    public static function saveUploadedFile(UploadedFile $file, string $folder = 'profile_photos', string $prefix = 'profile_'): ?string
    {
        $mime = $file->getMimeType();
        if (!$mime || !isset(self::$allowedMimeTypes[$mime])) {
            return null;
        }

        $extension = self::$allowedMimeTypes[$mime];
        $filename = $prefix . Str::random(20) . '_' . time() . '.' . $extension;

        $path = $file->storeAs($folder, $filename, 'public');
        if (!$path) {
            return null;
        }

        return '/storage/' . $path;
    }
}
