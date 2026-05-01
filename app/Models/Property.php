<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'property_type',
        'status',
        'featured',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'latitude',
        'longitude',
        'bedrooms',
        'bathrooms',
        'features',
        'sq_ft',
        'lot_size',
        'year_built',
        'garage',
        'has_pool',
        'has_garden',
        'has_garage',
        'has_parking',
        'has_security',
        'has_air_conditioning',
        'has_heating',
        'agent_id',
        'property_code'
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

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function getPrimaryImageAttribute()
    {
        return $this->images()->where('is_primary', true)->first()
            ?? $this->images()->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeWithFilters($query, $filters, $user)
    {
        return $query->when($filters['min_price'] ?? false, fn($q, $min) => $q->where('price', '>=', $min))
            ->when($filters['max_price'] ?? false, fn($q, $max) => $q->where('price', '<=', $max))
            ->when($filters['search'] ?? false, fn($q, $search) => $q->where('title', 'like', "%$search%"))
            ->when($filters['property_type'] ?? false, fn($q, $type) => $q->where('property_type', $type))
            ->when($filters['bedrooms'] ?? false, fn($q, $beds) => $q->where('bedrooms', '>=', $beds))
            ->when($filters['bathrooms'] ?? false, fn($q, $baths) => $q->where('bathrooms', '>=', $baths))
            ->when($filters['isUserFavorite'] ?? false, function ($q) use ($user) {
                $q->whereHas('favorites', function ($subQuery) use ($user) {
                    $subQuery->where('user_id', $user->id);
                });
            })
            ->when($filters['location'] ?? false, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('city', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%")
                        ->orWhere('zip_code', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            });


        //  ->when($filters['state'] ?? false, fn($q, $state) => $q->where('state', 'like', "%$state%"))
        //  ->when($filters['zip_code'] ?? false, fn($q, $zip) => $q->where('zip_code', 'like', "%$zip%"))
        //  ->when($filters['country'] ?? false, fn($q, $country) => $q->where('country', 'like', "%$country%"));
    }

    public function getViewsCountAttribute()
    {
        return $this->view_count;
    }

    public function getRecentViewsAttribute()
    {
        return $this->views()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
    }

    /**
     * Get all offers for this property
     */
    public function offers(): HasMany
    {
        return $this->hasMany(PropertyOffer::class);
    }

    /**
     * Get pending offers
     */
    public function pendingOffers(): HasMany
    {
        return $this->offers()->where('status', 'pending');
    }

    /**
     * Get accepted offers
     */
    public function acceptedOffers(): HasMany
    {
        return $this->offers()->where('status', 'accepted');
    }

    /**
     * Get the highest offer
     */
    public function highestOffer()
    {
        return $this->offers()
            ->where('status', '!=', 'rejected')
            ->orderBy('offer_amount', 'desc')
            ->first();
    }

    /**
     * Check if property has pending offers
     */
    public function hasPendingOffers(): bool
    {
        return $this->offers()->where('status', 'pending')->exists();
    }

    /**
     * Check if property has accepted offer
     */
    public function hasAcceptedOffer(): bool
    {
        return $this->offers()->where('status', 'accepted')->exists();
    }

    public function property_views(): HasMany
    {
        return $this->hasMany(PropertyView::class);
    }

    /**
     * Get today's views
     */
    public function todaysViews(): HasMany
    {
        return $this->property_views()->today();
    }

    /**
     * Get unique viewers count
     */
    public function getUniqueViewersCountAttribute(): int
    {
        return $this->property_views()
            ->select('ip_address')
            ->distinct()
            ->count('ip_address');
    }

    /**
     * Get average view duration
     */
    public function getAverageViewDurationAttribute(): float
    {
        return $this->property_views()->avg('view_duration') ?? 0;
    }

    /**
     * Get views by source
     */
    public function getViewsBySource()
    {
        return $this->property_views()
            ->selectRaw('view_source, COUNT(*) as count')
            ->groupBy('view_source')
            ->get()
            ->pluck('count', 'view_source');
    }

    /**
     * Increment view count and record view
     */
    public function recordView($userId = null, $source = 'direct', $duration = 0): PropertyView
    {
        // Update the cached view_count for quick access
        $this->increment('view_count');
        return PropertyView::recordView($this->id, $userId, $source, $duration);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /**
     * Get new/pending inquiries
     */
    public function pendingInquiries(): HasMany
    {
        return $this->inquiries()->pending();
    }

    /**
     * Get inquiry count
     */
    public function getInquiriesCountAttribute(): int
    {
        return $this->inquiries()->count();
    }

    /**
     * Get new inquiries count
     */
    public function getNewInquiriesCountAttribute(): int
    {
        return $this->inquiries()->new()->count();
    }

    // public function inquiries_responses(): HasMany
    // {
    //     return $this->hasMany::class);
    // }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($property) {
            if (empty($property->slug)) {
                $property->slug = static::buildUniqueSlug($property);
            }

            if (empty($property->property_code)) {
                $nextId = Property::max('id') + 1;
                $property->property_code = 'PR-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function ($property) {
            if ($property->isDirty(['title', 'city', 'state']) && empty($property->getOriginal('slug'))) {
                $property->slug = static::buildUniqueSlug($property);
            }
        });
    }

    protected static function buildUniqueSlug(Property $property): string
    {
        $base = Str::slug(collect([$property->title, $property->city, $property->state])->filter()->implode(' '));
        $base = $base ?: 'property';
        $slug = $base;
        $counter = 2;

        while (static::where('slug', $slug)->when($property->exists, fn ($query) => $query->whereKeyNot($property->id))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    public function purchases()
    {
        return $this->hasMany(PropertyPurchase::class);
    }
}
