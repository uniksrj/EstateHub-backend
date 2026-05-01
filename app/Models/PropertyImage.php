<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyImage extends Model
{
   use HasFactory;

    protected $fillable = [
         'property_id',
         'image_path',
         'cloudinary_public_id',
         'cloudinary_secure_url',
         'is_primary',
         'caption',
         'order_index',
    ];

    protected $appends = [
        'optimized_url',
        'thumbnail_url',
        'detail_url',
    ];

    protected $casts = [
        'is_primary' => 'boolean', 
    ];

    public function property(){
        return $this->belongsTo(Property::class);
    }

    public function getOptimizedUrlAttribute(): ?string
    {
        return $this->cloudinaryUrl('f_auto,q_auto');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->cloudinaryUrl('f_auto,q_auto,c_fill,w_480,h_320');
    }

    public function getDetailUrlAttribute(): ?string
    {
        return $this->cloudinaryUrl('f_auto,q_auto,c_fill,w_1280,h_720');
    }

    private function cloudinaryUrl(string $transformation): ?string
    {
        $url = $this->cloudinary_secure_url ?: $this->image_path;

        if (!$url) {
            return null;
        }

        if (!str_contains($url, 'res.cloudinary.com') || str_contains($url, '/upload/'.$transformation.'/')) {
            return $url;
        }

        return str_replace('/upload/', '/upload/'.$transformation.'/', $url);
    }
}
