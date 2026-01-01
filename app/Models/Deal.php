<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'offer_id',
        'property_id',
        'buyer_id',
        'seller_id',
        'agent_id',
        'final_price',
        'earnest_money_deposit',
        'status',
        'current_step',
        'progress_percentage',
        'accepted_date',
        'expected_closing_date',
        'actual_closing_date',
        'inspection_deadline',
        'mortgage_deadline',
        'appraisal_deadline',
        'title_company',
        'lender_name',
        'inspection_company',
        'special_terms',
        'notes',
        'deadline_extensions',
        'last_extension_date',
        'extension_reason',
        'deadline_status',
        'priority_override',
        'next_step_override',
        'priority_override',
        'next_step_override',        
    ];

    protected $casts = [
        'final_price' => 'decimal:2',
        'earnest_money_deposit' => 'decimal:2',
        'accepted_date' => 'date',
        'expected_closing_date' => 'date',
        'actual_closing_date' => 'date',
        'inspection_deadline' => 'date',
        'mortgage_deadline' => 'date',
        'appraisal_deadline' => 'date',
        'last_extension_date' => 'datetime',
        'deadline_extensions' => 'integer'
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(PropertyOffer::class, 'offer_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DealDocument::class);
    }

    public function scopeUnderContract($query)
    {
        return $query->where('status', 'under_contract');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['under_contract', 'pending_sale', 'closing']);
    }

    public function markStepComplete(string $nextStep): void
    {
        $steps = [
            'contract_generation' => 20,
            'earnest_money' => 40,
            'inspection' => 60,
            'mortgage_processing' => 80,
            'closing_preparation' => 90,
            'closed' => 100
        ];

        $this->update([
            'current_step' => $nextStep,
            'progress_percentage' => $steps[$nextStep] ?? $this->progress_percentage,
            'status' => $nextStep === 'closed' ? 'closed' : $this->status
        ]);
    }

    public function getNextStep(): string
    {
        $steps = [
            'contract_generation' => 'earnest_money',
            'earnest_money' => 'inspection',
            'inspection' => 'mortgage_processing',
            'mortgage_processing' => 'closing_preparation',
            'closing_preparation' => 'closed'
        ];

        return $steps[$this->current_step] ?? 'closed';
    }
}
