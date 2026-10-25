<?php

namespace App\Services;

use App\Contracts\MediaStorageInterface;
use Illuminate\Http\UploadedFile;

class MediaUploadService implements MediaStorageInterface
{
    public function __construct(
        protected CloudinaryService $cloudinaryService,
        protected S3StorageService $s3StorageService
    ) {}

    /**
     * Get the active storage driver instance based on config/env.
     */
    public function getDriver(): MediaStorageInterface
    {
        $provider = $this->getActiveProviderName();

        return match ($provider) {
            's3' => $this->s3StorageService,
            default => $this->cloudinaryService,
        };
    }

    /**
     * Get the active provider name ('cloudinary' or 's3').
     */
    public function getActiveProviderName(): string
    {
        $provider = strtolower((string) config('media.provider', 'cloudinary'));

        return in_array($provider, ['s3', 'aws'], true) ? 's3' : 'cloudinary';
    }

    /**
     * Upload a property image using the active provider.
     */
    public function uploadPropertyImage(UploadedFile $image, string $slug, int $index): array
    {
        return $this->getDriver()->uploadPropertyImage($image, $slug, $index);
    }

    /**
     * Upload a user avatar using the active provider.
     */
    public function uploadUserAvatar(UploadedFile $image, int|string $userId): array
    {
        return $this->getDriver()->uploadUserAvatar($image, $userId);
    }

    /**
     * Upload a beta feedback screenshot using the active provider.
     */
    public function uploadFeedbackScreenshot(UploadedFile $image): array
    {
        return $this->getDriver()->uploadFeedbackScreenshot($image);
    }

    /**
     * Delete an uploaded media item using the active provider.
     */
    public function delete(string $publicId): bool
    {
        return $this->getDriver()->delete($publicId);
    }
}
