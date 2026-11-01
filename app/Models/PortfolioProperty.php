<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioProperty extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SOLD = 'sold';
    public const STATUS_VACANT = 'vacant';

    protected $table = 'portfolio_properties';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'property_id',
        'purchase_price',
        'acquisition_costs',
        'monthly_rent',
        'monthly_opex',
        'acquired_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'property_id' => 'integer',
        'purchase_price' => 'decimal:2',
        'acquisition_costs' => 'decimal:2',
        'monthly_rent' => 'decimal:2',
        'monthly_opex' => 'decimal:2',
        'acquired_at' => 'date',
    ];

    /**
     * User who owns this portfolio property.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The linked property listing/entity.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeSold(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SOLD);
    }

    public function scopeVacant(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VACANT);
    }

    /**
     * Total cost basis including acquisition costs.
     */
    public function getTotalCostBasisAttribute(): float
    {
        return (float) $this->purchase_price + (float) $this->acquisition_costs;
    }

    /**
     * Net monthly cash flow (rent - operating expenses).
     */
    public function getMonthlyNetOperatingIncomeAttribute(): float
    {
        return (float) $this->monthly_rent - (float) $this->monthly_opex;
    }

    /**
     * Annualized NOI based on monthly cash flow.
     */
    public function getAnnualNetOperatingIncomeAttribute(): float
    {
        return $this->getMonthlyNetOperatingIncomeAttribute() * 12;
    }
}
