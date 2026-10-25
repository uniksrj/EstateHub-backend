<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface MediaStorageInterface
{
    /**
     * Upload a property image.
     *
     * @param UploadedFile $image
     * @param string $slug
     * @param int $index
     * @return array{secure_url: string, public_id: string}
     */
    public function uploadPropertyImage(UploadedFile $image, string $slug, int $index): array;

    /**
     * Upload a user avatar.
     *
     * @param UploadedFile $image
     * @param int|string $userId
     * @return array{secure_url: string, public_id: string}
     */
    public function uploadUserAvatar(UploadedFile $image, int|string $userId): array;

    /**
     * Upload a beta feedback screenshot.
     *
     * @param UploadedFile $image
     * @return array{secure_url: string, public_id: string}
     */
    public function uploadFeedbackScreenshot(UploadedFile $image): array;

    /**
     * Delete an uploaded file by its public ID or S3 object key.
     *
     * @param string $publicId
     * @return bool
     */
    public function delete(string $publicId): bool;
}
