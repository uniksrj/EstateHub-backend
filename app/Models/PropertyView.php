<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyView extends Model
{

    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'property_id',
        'view_duration',
        'view_source',
        'ip_address',
        'user_agent',
        'session_id',
        'viewed_at'
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'view_duration' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Scope for views from today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('viewed_at', today());
    }

    /**
     * Scope for views from this week
     */
    public function scopeThisWeek($query)
    {
        return $query->where('viewed_at', '>=', now()->startOfWeek());
    }

    /**
     * Scope for views from this month
     */
    public function scopeThisMonth($query)
    {
        return $query->where('viewed_at', '>=', now()->startOfMonth());
    }

    /**
     * Scope for unique views (by IP or user)
     */
    public function scopeUniqueViews($query)
    {
        return $query->select('ip_address', 'user_id')
            ->groupBy('ip_address', 'user_id');
    }

    /**
     * Scope for views from specific source
     */
    public function scopeFromSource($query, $source)
    {
        return $query->where('view_source', $source);
    }

    /**
     * Check if view is from a returning visitor
     */
    public function isReturningVisitor(): bool
    {
        return self::where('ip_address', $this->ip_address)
            ->where('property_id', $this->property_id)
            ->where('id', '!=', $this->id)
            ->exists();
    }

    /**
     * Get view duration in readable format
     */
    public function getReadableDurationAttribute(): string
    {
        if ($this->view_duration < 60) {
            return $this->view_duration . ' seconds';
        }

        $minutes = floor($this->view_duration / 60);
        $seconds = $this->view_duration % 60;

        return $minutes . ' min ' . $seconds . ' sec';
    }

    /**
     * Check if view was from a mobile device
     */
    public function isMobileDevice(): bool
    {
        return preg_match('/iPhone|Android|Mobile|iPad/i', $this->user_agent);
    }

    /**
     * Get the view source label
     */
    public function getViewSourceLabelAttribute(): string
    {
        $sources = [
            'direct' => 'Direct Visit',
            'search' => 'Search Engine',
            'social' => 'Social Media',
            'email' => 'Email Campaign',
            'referral' => 'Referral',
            'organic' => 'Organic Search'
        ];

        return $sources[$this->view_source] ?? ucfirst($this->view_source);
    }

    /**
     * Record a new property view
     */
    public static function recordView($propertyId, $userId = null, $source = 'direct', $duration = 0): self
    {
        return self::create([
            'property_id' => $propertyId,
            'user_id' => $userId,
            'view_source' => $source,
            'view_duration' => $duration,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'viewed_at' => now()
        ]);
    }

    /**
     * Check if user has viewed this property recently (within 30 minutes)
     */
    public static function hasRecentView($propertyId, $sessionId = null): bool
    {
        return self::where('property_id', $propertyId)
            ->where('session_id', $sessionId ?: session()->getId())
            ->where('viewed_at', '>=', now()->subMinutes(30))
            ->exists();
    }
}
