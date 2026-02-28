<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyOffer extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'property_id',
        'buyer_id',
        'offer_amount',
        'message',
        'status',
        'expires_at',
        'counter_offer_amount',
        'counter_offer_message',
        'commission_rate',
        'special_conditions',
        'offer_date',
        'accepted_at',
        'rejected_at'
    ];

    protected $casts = [
        'offer_amount' => 'decimal:2',
        'counter_offer_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'expires_at' => 'datetime',
        'offer_date' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class, 'property_id');
    }    

    /**
     * Scope for pending offers
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for accepted offers
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Connection between offer and loan applications (if any)
     */
    public function loanApplications(){
        return $this->hasMany(LoanApplications::class, 'offer_id')->latest();
    }

    /**
     * Scope for rejected offers
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for expired offers
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', 'pending');
    }

    /**
     * Check if offer is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status === 'pending';
    }

    /**
     * Check if offer can be accepted
     */
    public function canBeAccepted(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Accept the offer
     */
    public function accept(): bool
    {
        if (!$this->canBeAccepted()) {
            return false;
        }

        return $this->update([
            'status' => 'accepted',
            'accepted_at' => now()
        ]);
    }

    /**
     * Reject the offer
     */
    public function reject(string $reason = ''): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'message' => $reason ? $this->message . " [Rejected: $reason]" : $this->message
        ]);
    }

    /**
     * Make a counter offer
     */
    public function counterOffer(float $amount, string $message = ''): bool
    {
        return $this->update([
            'counter_offer_amount' => $amount,
            'counter_offer_message' => $message,
            'status' => 'countered'
        ]);
    }

    /**
     * Get formatted offer amount
     */
    public function getFormattedOfferAmountAttribute(): string
    {
        return '$' . number_format($this->offer_amount, 2);
    }

    /**
     * Get formatted counter offer amount
     */
    public function getFormattedCounterOfferAmountAttribute(): ?string
    {
        return $this->counter_offer_amount ? '$' . number_format($this->counter_offer_amount, 2) : null;
    }

    /**
     * Calculate commission amount
     */
    public function getCommissionAmountAttribute(): float
    {
        return $this->offer_amount * ($this->commission_rate / 100);
    }

    /**
     * Get net amount after commission
     */
    public function getNetAmountAttribute(): float
    {
        return $this->offer_amount - $this->commission_amount;
    }

}
