<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketSnapshot extends Model
{
    use HasFactory;

    protected $table = 'market_analytics_snapshots';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'city',
        'locality',
        'snapshot_date',
        'median_price_per_sqft',
        'median_rent_per_sqft',
        'yoy_appreciation_pct',
        'avg_gross_yield',
        'active_listings',
        'avg_days_on_market',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'snapshot_date' => 'date',
        'median_price_per_sqft' => 'decimal:2',
        'median_rent_per_sqft' => 'decimal:2',
        'yoy_appreciation_pct' => 'decimal:3',
        'avg_gross_yield' => 'decimal:3',
        'active_listings' => 'integer',
        'avg_days_on_market' => 'integer',
    ];

    public function scopeForCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeForLocality(Builder $query, string $city, ?string $locality = null): Builder
    {
        $query->where('city', $city);

        if ($locality !== null) {
            $query->where('locality', $locality);
        }

        return $query;
    }

    public function scopeLatestSnapshot(Builder $query): Builder
    {
        return $query->orderByDesc('snapshot_date');
    }
}
