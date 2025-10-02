<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
         'title', 'description', 'price', 'property_type', 'status', 'featured',
        'address', 'city', 'state', 'zip_code', 'country', 'latitude', 'longitude',
        'bedrooms', 'bathrooms', 'sq_ft', 'lot_size', 'year_built', 'garage',
        'has_pool', 'has_garden', 'has_garage', 'has_parking', 
        'has_security', 'has_air_conditioning', 'has_heating', 'agent_id'
    ];

    protected $casts = [
         'price' => 'decimal:2',
        'featured' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'sq_ft' => 'integer',
        'has_pool' => 'boolean',
        'has_garden' => 'boolean',
        'has_garage' => 'boolean',
        'has_parking' => 'boolean',
        'has_security' => 'boolean',
        'has_air_conditioning' => 'boolean',
        'has_heating' => 'boolean',
    ];

    public function agent(){
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function images(){
        return $this->hasMany(PropertyImage::class);
    }

    public function inquiries(){
        return $this->hasMany(Inquiry::class);
    }   

    public function favorites() {
        return $this->hasMany(Favorite::class);
    }

    public function getPrimaryImageAttribute() {
        return $this->images()->where('is_primary', true)->first() 
            ?? $this->images()->first();
    }
    
    public function scopeActive($query){
        return $query->where('status', 'available');
    }

    public function scopeFeatured($query){
        return $query->where('featured', true);
    }

    public function scopeWithFilters($query , $filters){
        return $query->when($filters['min_price'] ?? false, fn($q, $min) => $q->where('price', '>=', $min))
                     ->when($filters['max_price'] ?? false, fn($q, $max) => $q->where('price', '<=', $max))
                     ->when($filters['search'] ?? false, fn($q, $search) => $q->where('title', 'like', "%$search%"))
                     ->when($filters['property_type'] ?? false, fn($q, $type) => $q->where('property_type', $type))
                     ->when($filters['bedrooms'] ?? false, fn($q, $beds) => $q->where('bedrooms', '>=', $beds))
                     ->when($filters['bathrooms'] ?? false, fn($q, $baths) => $q->where('bathrooms', '>=', $baths))
                     ->when($filters['location'] ?? false, function ($q, $search) {
                        $q->where(function ($q) use ($search) {
                                $q->where('city', 'like', "%{$search}%")
                                ->orWhere('state', 'like', "%{$search}%")
                                ->orWhere('zip_code', 'like', "%{$search}%")
                                ->orWhere('country', 'like', "%{$search}%");
                        });
                    });
                     
                    //  ->when($filters['city'] ?? false, fn($q, $city) => $q->where('city', 'like', "%$city%"))
                    //  ->when($filters['state'] ?? false, fn($q, $state) => $q->where('state', 'like', "%$state%"))
                    //  ->when($filters['zip_code'] ?? false, fn($q, $zip) => $q->where('zip_code', 'like', "%$zip%"))
                    //  ->when($filters['country'] ?? false, fn($q, $country) => $q->where('country', 'like', "%$country%"));
    }
}
