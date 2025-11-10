<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PropertyAlert extends Model
{
    use HasFactory;

    /**
     * Alert status constants.
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_DISABLED = 'disabled';

    /**
     * Frequency constants.
     */
    const FREQUENCY_INSTANT = 'instant';
    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'preference_id',
        'name',
        'criteria',
        'is_active',
        'frequency',
        'match_count',
        'total_matches',
        'last_matched_at',
        'last_notified_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
        'last_matched_at' => 'datetime',
        'last_notified_at' => 'datetime',
    ];

    /**
     * Default attribute values.
     *
     * @var array
     */
    protected $attributes = [
        'is_active' => true,
        'match_count' => 0,
    ];

    /**
     * Get the user that owns the alert.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the preference associated with the alert.
     */
    public function preference(): BelongsTo
    {
        return $this->belongsTo(BuyerPreference::class);
    }

    /**
     * Get the matching properties.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'alert_property_matches')
            ->withPivot(['matched_at', 'notified_at', 'is_new'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include alerts with new matches.
     */
    public function scopeWithNewMatches($query)
    {
        return $query->where('match_count', '>', 0)
            ->where(function ($q) {
                $q->whereNull('last_notified_at')
                    ->orWhereRaw('last_matched_at > last_notified_at');
            });
    }

    /**
     * Scope a query by frequency.
     */
    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Check if alert has new matches.
     */
    public function hasNewMatches(): bool
    {
        return $this->match_count > 0 &&
            (!$this->last_notified_at ||
                $this->last_matched_at > $this->last_notified_at);
    }

    /**
     * Get new matches count.
     */
    public function getNewMatchesCountAttribute(): int
    {
        return $this->hasNewMatches() ? $this->match_count : 0;
    }

    /**
     * Activate the alert.
     */
    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'is_active' => true
        ]);
    }

    /**
     * Pause the alert.
     */
    public function pause(): void
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
            'is_active' => false
        ]);
    }

    /**
     * Disable the alert.
     */
    public function disable(): void
    {
        $this->update([
            'status' => self::STATUS_DISABLED,
            'is_active' => false
        ]);
    }

    /**
     * Increment match count and update timestamp.
     */
    public function incrementMatches(int $count = 1): void
    {
        $this->update([
            'match_count' => $this->match_count + $count,
            'total_matches' => $this->total_matches + $count,
            'last_matched_at' => now()
        ]);
    }

    /**
     * Reset match count (after notifications sent).
     */
    public function resetMatchCount(): void
    {
        $this->update([
            'match_count' => 0,
            'last_notified_at' => now()
        ]);
    }

    /**
     * Get criteria summary for display.
     */
    public function getCriteriaSummaryAttribute(): string
    {
        $criteria = $this->criteria ?? [];
        $summary = [];

        if (isset($criteria['min_price']) && isset($criteria['max_price'])) {
            $summary[] = '$' . number_format($criteria['min_price']) . ' - $' . number_format($criteria['max_price']);
        }

        if (isset($criteria['min_bedrooms'])) {
            $summary[] = $criteria['min_bedrooms'] . '+ beds';
        }

        if (isset($criteria['property_type'])) {
            $summary[] = ucfirst($criteria['property_type']);
        }

        if (isset($criteria['location'])) {
            $summary[] = $criteria['location'];
        }

        return implode(' • ', $summary);
    }

    /**
     * Check if alert should send notifications based on threshold.
     */
    public function shouldSendNotification(): bool
    {
        return $this->match_count >= $this->notification_threshold;
    }

    /**
     * Get the alert's matching properties query.
     */
    public function getMatchingPropertiesQuery()
    {
        $criteria = $this->criteria ?? [];
        $query = Property::where('status', 'active');

        // Apply price criteria
        if (isset($criteria['min_price'])) {
            $query->where('price', '>=', $criteria['min_price']);
        }
        if (isset($criteria['max_price'])) {
            $query->where('price', '<=', $criteria['max_price']);
        }

        // Apply bedroom criteria
        if (isset($criteria['min_bedrooms'])) {
            $query->where('bedrooms', '>=', $criteria['min_bedrooms']);
        }

        // Apply property type criteria
        if (isset($criteria['property_type'])) {
            $query->where('type', $criteria['property_type']);
        }

        // Apply location criteria (simplified)
        if (isset($criteria['location'])) {
            $query->where('city', 'like', '%' . $criteria['location'] . '%')
                ->orWhere('neighborhood', 'like', '%' . $criteria['location'] . '%');
        }

        return $query;
    }
}
