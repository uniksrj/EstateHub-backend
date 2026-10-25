<?php

namespace App\Services;

use App\Contracts\MediaStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class S3StorageService implements MediaStorageInterface
{
    /**
     * Upload a property image to S3.
     */
    public function uploadPropertyImage(UploadedFile $image, string $slug, int $index): array
    {
        $sanitizedSlug = Str::slug($slug) ?: 'property';
        $uniqueSuffix = Str::random(10);
        $filename = "{$sanitizedSlug}-" . ($index + 1) . "-{$uniqueSuffix}";

        return $this->upload($image, 'properties', $filename);
    }

    /**
     * Upload a user avatar to S3.
     */
    public function uploadUserAvatar(UploadedFile $image, int|string $userId): array
    {
        $sanitizedUserId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $userId);
        $uniqueSuffix = Str::random(10);
        $filename = "user-{$sanitizedUserId}-avatar-{$uniqueSuffix}";

        return $this->upload($image, 'avatars', $filename);
    }

    /**
     * Upload a beta feedback screenshot to S3.
     */
    public function uploadFeedbackScreenshot(UploadedFile $image): array
    {
        $uniqueSuffix = Str::random(12);
        $filename = "feedback-screenshot-{$uniqueSuffix}";

        return $this->upload($image, 'beta-feedback', $filename);
    }

    /**
     * Delete an uploaded file by its S3 object key.
     */
    public function delete(string $publicId): bool
    {
        try {
            $disk = Storage::disk(config('media.s3.disk', 's3'));
            return $disk->delete($publicId);
        } catch (\Throwable $e) {
            Log::warning('AWS S3 file delete failed: ' . $e->getMessage(), ['key' => $publicId]);
            return false;
        }
    }

    /**
     * Internal upload handler with security validation, content types, and CDN/custom URL generation.
     */
    protected function upload(UploadedFile $file, string $folder, string $filename): array
    {
        $this->validateFile($file);

        $diskName = config('media.s3.disk', 's3');
        $bucket = config('media.s3.bucket') ?: config('filesystems.disks.s3.bucket');
        $key = config('filesystems.disks.s3.key');
        $secret = config('filesystems.disks.s3.secret');

        if (empty($bucket) || empty($key) || empty($secret)) {
            throw new RuntimeException('AWS S3 is not configured. Please ensure AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, and AWS_BUCKET are set in your environment.');
        }

        $extension = strtolower($file->getClientOriginalExtension()) ?: $file->guessExtension() ?: 'jpg';
        $rootPrefix = trim(config('media.s3.prefix', 'estate-hub'), '/');
        $cleanFolder = trim($folder, '/');
        $targetFolder = $rootPrefix ? "{$rootPrefix}/{$cleanFolder}" : $cleanFolder;
        $targetFilename = "{$filename}.{$extension}";
        $objectKey = "{$targetFolder}/{$targetFilename}";

        $mimeType = $file->getMimeType() ?: 'image/jpeg';
        $cacheControl = config('media.s3.cache_control', 'max-age=31536000, public');

        $options = [
            'ContentType' => $mimeType,
            'CacheControl' => $cacheControl,
        ];

        $visibility = config('media.s3.visibility');
        if ($visibility) {
            $options['visibility'] = $visibility;
        }

        try {
            $disk = Storage::disk($diskName);
            $stream = fopen($file->getRealPath(), 'r');

            if ($stream === false) {
                throw new RuntimeException("Could not open file stream for upload.");
            }

            try {
                $saved = $disk->put($objectKey, $stream, $options);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (!$saved) {
                throw new RuntimeException("S3 driver failed to store object at: {$objectKey}");
            }

            // Determine secure public URL
            $customUrl = config('media.s3.url') ?: config('filesystems.disks.s3.url');
            if (!empty($customUrl)) {
                $secureUrl = rtrim($customUrl, '/') . '/' . ltrim($objectKey, '/');
            } else {
                $secureUrl = $disk->url($objectKey);
            }

            return [
                'secure_url' => $secureUrl,
                'public_id' => $objectKey,
            ];
        } catch (\Throwable $e) {
            Log::error('AWS S3 upload error', [
                'message' => $e->getMessage(),
                'key' => $objectKey,
                'file' => $file->getClientOriginalName(),
            ]);

            throw new RuntimeException('AWS S3 upload failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Strict security validation: checks upload health, MIME whitelist, extension whitelist, and max file size.
     */
    protected function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('Uploaded file is corrupted or failed validation: ' . $file->getErrorMessage());
        }

        $allowedMimes = config('media.validation.allowed_mimes', [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml', 'image/avif'
        ]);

        $mimeType = strtolower((string) $file->getMimeType());
        if ($mimeType && !in_array($mimeType, $allowedMimes, true)) {
            throw new InvalidArgumentException("Unsupported media type [{$mimeType}]. Allowed: " . implode(', ', $allowedMimes));
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $allowedExtensions = config('media.validation.allowed_extensions', [
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif'
        ]);
        if ($extension && !in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException("Unsupported file extension [.{$extension}]. Allowed: " . implode(', ', $allowedExtensions));
        }

        $maxKb = (int) config('media.validation.max_file_size', 10240);
        if ($file->getSize() > ($maxKb * 1024)) {
            throw new InvalidArgumentException("File size exceeds the limit of " . round($maxKb / 1024, 1) . " MB.");
        }
    }
}
