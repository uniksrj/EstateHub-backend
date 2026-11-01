<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentWatchlist extends Model
{
    use HasFactory;

    protected $table = 'investment_watchlists';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'property_id',
        'alert_min_cap_rate',
        'alert_max_price',
        'notify_on_price_drop',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'property_id' => 'integer',
        'alert_min_cap_rate' => 'decimal:2',
        'alert_max_price' => 'decimal:2',
        'notify_on_price_drop' => 'boolean',
    ];

    /**
     * User who created the watchlist entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Property being tracked.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
