<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CloudinaryService
{
    public function uploadPropertyImage(UploadedFile $image, string $slug, int $index): array
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');
        
        if (!$cloudName || !$apiKey || !$apiSecret) {
            throw new RuntimeException('Cloudinary is not configured.');
        }   

        $timestamp = time();
        $folder = trim(config('cloudinary.folder'), '/');
        $publicId = Str::slug($slug).'-'.($index + 1).'-'.Str::random(8);

        $params = [
            'folder' => $folder,
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];

        ksort($params);
        $signaturePayload = collect($params)
            ->map(fn ($value, $key) => $key.'='.$value)
            ->implode('&');

        $response = Http::attach(
            'file',
            file_get_contents($image->getRealPath()),
            $image->getClientOriginalName()
        )->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
            ...$params,
            'api_key' => $apiKey,
            'signature' => sha1($signaturePayload.$apiSecret),
            'resource_type' => 'image',
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Cloudinary upload failed: '.$response->body());
        }

        return [
            'secure_url' => $response->json('secure_url'),
            'public_id' => $response->json('public_id'),
        ];
    }
}
