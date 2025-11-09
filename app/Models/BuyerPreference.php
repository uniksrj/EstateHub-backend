<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuyerPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'min_price',
        'max_price',
        'min_bedrooms',
        'min_bathrooms',
        'property_type',
        'location',
        'min_sqft',
        'max_sqft',
        'amenities',
        'alerts_enabled',
        'alert_frequency',
        'location_radius',
        'year_built_min',
        'year_built_max',
        'lot_size_min',
        'lot_size_max',
        'has_garage',
        'has_pool',
        'has_garden',
        'is_default',
        'created_at',
        'updated_at',
        'name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'min_bedrooms' => 'integer',
        'min_bathrooms' => 'integer',
        'min_sqft' => 'integer',
        'max_sqft' => 'integer',
        'amenities' => 'array',
        'alerts_enabled' => 'boolean',
        'location_radius' => 'integer',
        'year_built_min' => 'integer',
        'year_built_max' => 'integer',
        'lot_size_min' => 'integer',
        'lot_size_max' => 'integer',
        'has_garage' => 'boolean',
        'has_pool' => 'boolean',
        'has_garden' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default attribute values.
     *
     * @var array
     */
    protected $attributes = [
        'alerts_enabled' => true,
        'alert_frequency' => 'instant',
        'amenities' => '[]',
    ];

    /**
     * Get the user that owns the preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the alerts for these preferences.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(PropertyAlert::class, 'preference_id');
    }

    /**
     * Scope a query to only include active alerts.
     */
    public function scopeWithActiveAlerts($query)
    {
        return $query->where('alerts_enabled', true);
    }

    /**
     * Check if preferences have price range set.
     */
    public function hasPriceRange(): bool
    {
        return !is_null($this->min_price) && !is_null($this->max_price);
    }

    /**
     * Get price range as array.
     */
    public function getPriceRangeAttribute(): array
    {
        return [
            'min' => $this->min_price,
            'max' => $this->max_price
        ];
    }

    /**
     * Get bedroom range.
     */
    public function getBedroomRangeAttribute(): ?int
    {
        return $this->min_bedrooms;
    }

    /**
     * Get bathroom range.
     */
    public function getBathroomRangeAttribute(): ?int
    {
        return $this->min_bathrooms;
    }

    /**
     * Get square footage range.
     */
    public function getSquareFootageRangeAttribute(): array
    {
        return [
            'min' => $this->min_sqft,
            'max' => $this->max_sqft
        ];
    }

    /**
     * Check if specific amenity is required.
     */
    public function requiresAmenity(string $amenity): bool
    {
        return in_array($amenity, $this->amenities ?? []);
    }

    /**
     * Get search criteria summary.
     */
    public function getCriteriaSummaryAttribute(): string
    {
        $criteria = [];

        if ($this->hasPriceRange()) {
            $criteria[] = '$' . number_format($this->min_price) . ' - $' . number_format($this->max_price);
        }

        if ($this->min_bedrooms) {
            $criteria[] = $this->min_bedrooms . '+ beds';
        }

        if ($this->min_bathrooms) {
            $criteria[] = $this->min_bathrooms . '+ baths';
        }

        if ($this->property_type) {
            $criteria[] = ucfirst($this->property_type);
        }

        if ($this->location) {
            $criteria[] = $this->location;
        }

        return implode(' • ', $criteria);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for default preference
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Set as default preference
    public function setAsDefault()
    {
        // Remove default from other preferences
        $this->user->preferences()->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }
    
}
