<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active Media Storage Provider
    |--------------------------------------------------------------------------
    | Options: "cloudinary", "s3"
    | Default: "cloudinary"
    |
    | You can switch between providers via STORAGE_PROVIDER or UPLOAD_PROVIDER in .env
    */
    'provider' => env('STORAGE_PROVIDER', env('UPLOAD_PROVIDER', 'cloudinary')),

    /*
    |--------------------------------------------------------------------------
    | AWS S3 Configuration
    |--------------------------------------------------------------------------
    */
    's3' => [
        'disk' => 's3',
        'bucket' => env('AWS_BUCKET'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'url' => env('AWS_URL'),
        'prefix' => env('AWS_S3_FOLDER', 'estate-hub'),
        'visibility' => env('AWS_VISIBILITY', null), // null respects bucket policy / ownership
        'cache_control' => env('AWS_CACHE_CONTROL', 'max-age=31536000, public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    */
    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'folder' => env('CLOUDINARY_FOLDER', 'estate-hub/properties'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & File Validation Rules
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
            'image/avif',
        ],
        'allowed_extensions' => [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'gif',
            'svg',
            'avif',
        ],
        'max_file_size' => 10240, // 10MB in KB
    ],
];
