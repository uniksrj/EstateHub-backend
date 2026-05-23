<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{

    protected $table = 'inquiries';

    protected $fillable = [
        'property_id',
        'user_id',
        'name',
        'email',
        'phone',
        'message',
        'status',
        'source',
        'priority',
        'budget_min',
        'budget_max',
        'timeline',
        'property_type_interest',
        'preferred_location',
        'bedrooms',
        'bathrooms',
        'additional_requirements',
        'responded_at',
        'responded_by',
        'response_notes'
    ];

    protected $casts = [
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Status constants
    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_RESPONDED = 'responded';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_CLOSED = 'closed';
    const STATUS_SPAM = 'spam';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Source constants
    const SOURCE_WEBSITE = 'website';
    const SOURCE_PHONE = 'phone';
    const SOURCE_EMAIL = 'email';
    const SOURCE_REFERRAL = 'referral';
    const SOURCE_SOCIAL_MEDIA = 'social_media';

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by', 'id');
    }

    /**
     * Scope for new inquiries
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope for pending inquiries (new + contacted)
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_NEW, self::STATUS_CONTACTED]);
    }

    /**
     * Scope for responded inquiries
     */
    public function scopeResponded($query)
    {
        return $query->where('status', self::STATUS_RESPONDED);
    }

    /**
     * Scope for high priority inquiries
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope for inquiries from specific source
     */
    public function scopeFromSource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope for inquiries in last N days
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for inquiries needing follow-up
     */
    public function scopeNeedsFollowUp($query)
    {
        return $query->whereIn('status', [self::STATUS_NEW, self::STATUS_CONTACTED])
            ->where('created_at', '<=', now()->subDays(2));
    }

    /**
     * Check if inquiry is new
     */
    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    /**
     * Check if inquiry needs response
     */
    public function needsResponse(): bool
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_CONTACTED]);
    }

    /**
     * Check if inquiry is high priority
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Mark inquiry as contacted
     */
    public function markAsContacted($notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CONTACTED,
            'response_notes' => $notes ? ($this->response_notes . "\n" . $notes) : $this->response_notes
        ]);
    }

    /**
     * Mark inquiry as responded
     */
    public function markAsResponded($responderId, $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESPONDED,
            'responded_at' => now(),
            'responded_by' => $responderId,
            'response_notes' => $notes ? ($this->response_notes . "\n" . $notes) : $this->response_notes
        ]);
    }

    /**
     * Mark inquiry as scheduled
     */
    public function markAsScheduled($notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_SCHEDULED,
            'response_notes' => $notes ? ($this->response_notes . "\n" . $notes) : $this->response_notes
        ]);
    }

    /**
     * Close inquiry
     */
    public function close($notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CLOSED,
            'response_notes' => $notes ? ($this->response_notes . "\n" . $notes) : $this->response_notes
        ]);
    }

    /**
     * Get response time in hours
     */
    public function getResponseTimeAttribute(): ?float
    {
        if (!$this->responded_at) {
            return null;
        }

        return round($this->created_at->diffInHours($this->responded_at), 1);
    }

    /**
     * Get formatted budget range
     */
    public function getBudgetRangeAttribute(): string
    {
        if ($this->budget_min && $this->budget_max) {
            return '₹' . number_format($this->budget_min) . ' - ₹' . number_format($this->budget_max);
        } elseif ($this->budget_min) {
            return 'From ₹' . number_format($this->budget_min);
        } elseif ($this->budget_max) {
            return 'Up to ₹' . number_format($this->budget_max);
        }

        return 'Not specified';
    }

    /**
     * Get priority badge color
     */
    public function getPriorityBadgeColorAttribute(): string
    {
        $colors = [
            self::PRIORITY_LOW => 'bg-gray-100 text-gray-800',
            self::PRIORITY_MEDIUM => 'bg-blue-100 text-blue-800',
            self::PRIORITY_HIGH => 'bg-orange-100 text-orange-800',
            self::PRIORITY_URGENT => 'bg-red-100 text-red-800'
        ];

        return $colors[$this->priority] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        $colors = [
            self::STATUS_NEW => 'bg-green-100 text-green-800',
            self::STATUS_CONTACTED => 'bg-blue-100 text-blue-800',
            self::STATUS_RESPONDED => 'bg-purple-100 text-purple-800',
            self::STATUS_SCHEDULED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_CLOSED => 'bg-gray-100 text-gray-800',
            self::STATUS_SPAM => 'bg-red-100 text-red-800'
        ];

        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Create a new inquiry
     */
    public static function createInquiry(array $data): self
    {
        return self::create(array_merge([
            'status' => self::STATUS_NEW,
            'priority' => self::PRIORITY_MEDIUM,
            'source' => self::SOURCE_WEBSITE,
        ], $data));
    }

    /**
     * Get inquiries count by status for dashboard
     */
    public static function getStatusCounts($sellerId = null)
    {
        $query = self::query();

        if ($sellerId) {
            $query->whereHas('property', function ($q) use ($sellerId) {
                $q->where('agent_id', $sellerId);
            });
        }

        return $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
    }

    /**
     * Get the user who responded to the inquiry
     */
    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    /**
     * Get all responses for the inquiry
     */
    public function responses(): HasMany
    {
        return $this->hasMany(InquiryResponse::class);
    }

    /**
     * Get latest response
     */
    public function latestResponse()
    {
        return $this->hasOne(InquiryResponse::class)->latest();
    }


    /**
     * Check if inquiry has responses
     */
    public function hasResponses(): bool
    {
        return $this->responses()->exists();
    }

    /**
     * Get unread responses count
     */
    public function unreadResponsesCount(): int
    {
        return $this->responses()->unread()->count();
    }
   
}
