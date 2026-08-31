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
    /**
     * Whitelist of allowed MIME types and their corresponding safe extensions.
     */
    private static array $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
        'image/heic-sequence' => 'heic',
        'image/bmp' => 'bmp',
        'image/x-ms-bmp' => 'bmp',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'video/webm' => 'webm',
        'video/x-msvideo' => 'avi',
        'video/3gpp' => '3gp',
        'video/ogg' => 'ogg',
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
        @ini_set('memory_limit', '512M');
        try {
            $dataUrl = trim($dataUrl);
            if (empty($dataUrl) || !is_string($dataUrl)) {
                return null;
            }

            // Must contain base64 separator
            if (!str_contains($dataUrl, ';base64,')) {
                \Illuminate\Support\Facades\Log::warning('SecureFileUpload::saveBase64 failed: Missing base64 header separator.');
                return null;
            }

            $parts = explode(';base64,', $dataUrl);
            if (count($parts) !== 2) {
                \Illuminate\Support\Facades\Log::warning('SecureFileUpload::saveBase64 failed: Invalid base64 structure.');
                return null;
            }

            $base64String = $parts[1];
            unset($parts);

            $binaryData = base64_decode($base64String, true);
            unset($base64String);

            if ($binaryData === false) {
                \Illuminate\Support\Facades\Log::warning('SecureFileUpload::saveBase64 failed: Base64 decode returned false.');
                return null; // Invalid base64
            }

            // Enforce maximum size (10 MB)
            if (strlen($binaryData) > self::MAX_FILE_SIZE_BYTES) {
                \Illuminate\Support\Facades\Log::warning('SecureFileUpload::saveBase64 failed: File size (' . strlen($binaryData) . ' bytes) exceeds maximum limit.');
                unset($binaryData);
                return null;
            }

            // Inspect real MIME type from binary contents using finfo
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo->buffer($binaryData);

            if (!$detectedMime || !isset(self::$allowedMimeTypes[$detectedMime])) {
                \Illuminate\Support\Facades\Log::warning('SecureFileUpload::saveBase64 failed: Disallowed or undetected MIME type (' . ($detectedMime ?: 'unknown') . ').');
                unset($binaryData);
                return null; // Disallowed MIME type
            }

            $extension = self::$allowedMimeTypes[$detectedMime];
            $filename = $prefix . Str::random(20) . '_' . time() . '.' . $extension;

            Storage::disk('public')->makeDirectory($folder);
            $stored = Storage::disk('public')->put($folder . '/' . $filename, $binaryData);
            unset($binaryData);

            if (!$stored) {
                \Illuminate\Support\Facades\Log::error('SecureFileUpload::saveBase64 failed: Could not save file to disk ' . $folder . '/' . $filename);
                return null;
            }

            return '/storage/' . $folder . '/' . $filename;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SecureFileUpload::saveBase64 exception: ' . $e->getMessage());
            return null;
        }
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

    /**
     * Safely delete a file from public storage.
     */
    public static function deleteFile(?string $filePath): bool
    {
        if (!$filePath || !is_string($filePath)) {
            return false;
        }

        $cleanPath = preg_replace('#^/?storage/#', '', trim($filePath));
        if (Storage::disk('public')->exists($cleanPath)) {
            return Storage::disk('public')->delete($cleanPath);
        }

        return false;
    }
}
