<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyInvestmentMetric extends Model
{
    use HasFactory;

    protected $table = 'property_investment_metrics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'property_id',
        'gross_yield',
        'net_yield',
        'cap_rate',
        'noi_annual',
        'dscr',
        'cash_on_cash',
        'occupancy_rate',
        'investment_score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'property_id' => 'integer',
        'gross_yield' => 'decimal:3',
        'net_yield' => 'decimal:3',
        'cap_rate' => 'decimal:3',
        'noi_annual' => 'decimal:2',
        'dscr' => 'decimal:3',
        'cash_on_cash' => 'decimal:3',
        'occupancy_rate' => 'decimal:2',
        'investment_score' => 'integer',
    ];

    /**
     * Property associated with these computed metrics.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function scopeTopScored(Builder $query, int $minScore = 70): Builder
    {
        return $query->where('investment_score', '>=', $minScore);
    }

    public function scopeMinCapRate(Builder $query, float $minCapRate): Builder
    {
        return $query->where('cap_rate', '>=', $minCapRate);
    }
}
