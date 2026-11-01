<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentProfile extends Model
{
    use HasFactory;

    public const RISK_LOW = 'low';
    public const RISK_MODERATE = 'moderate';
    public const RISK_AGGRESSIVE = 'aggressive';

    protected $table = 'investment_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'risk_appetite',
        'budget_min',
        'budget_max',
        'target_cap_rate',
        'target_cash_on_cash',
        'hold_period_years',
        'preferred_cities',
        'preferred_property_types',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'target_cap_rate' => 'decimal:2',
        'target_cash_on_cash' => 'decimal:2',
        'hold_period_years' => 'integer',
        'preferred_cities' => 'array',
        'preferred_property_types' => 'array',
    ];

    /**
     * The user this profile belongs to (role 7 - Investor).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLowRisk(): bool
    {
        return $this->risk_appetite === self::RISK_LOW;
    }

    public function isModerateRisk(): bool
    {
        return $this->risk_appetite === self::RISK_MODERATE;
    }

    public function isAggressiveRisk(): bool
    {
        return $this->risk_appetite === self::RISK_AGGRESSIVE;
    }
}
