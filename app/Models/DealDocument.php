<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealDocument extends Model
{
    protected $fillable = [
        'deal_id',
        'property_offer_id',
        'uploaded_by',
        'document_name',
        'file_url',
        'file_size',
        'file_type',
        'external_file_id',
        'category',
        'document_type',
        'shared_with',
        'description',
        'requires_signature',
        'signed_at'
    ];

    protected $casts = [
        'shared_with' => 'array',
        'requires_signature' => 'boolean',
        'signed_at' => 'datetime'
    ];
    
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function propertyOffer(): BelongsTo
    {
        return $this->belongsTo(PropertyOffer::class, 'property_offer_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRequiredForStep($query, string $step)
    {
        $stepDocuments = [
            'contract_generation' => ['purchase_agreement', 'property_disclosures'],
            'earnest_money' => ['emd_receipt', 'wire_instructions'],
            'inspection' => ['home_inspection', 'pest_inspection'],
            'mortgage_processing' => ['loan_application', 'appraisal_report'],
            'closing_preparation' => ['closing_disclosure', 'settlement_statement']
        ];

        return $query->whereIn('document_type', $stepDocuments[$step] ?? []);
    }
}
