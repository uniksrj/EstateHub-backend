<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanApplications extends Model
{
     use HasFactory, SoftDeletes;

    protected $table = 'loan_applications';

    protected $fillable = [
        'user_id',
        'offer_id',
        'property_id',
        'application_number',
        'status',
        'loan_type',
        'loan_amount',
        'interest_rate',
        'loan_term',
        'monthly_payment',
        'down_payment',
        'down_payment_percentage',
        'property_address',
        'property_city',
        'property_state',
        'property_zip',
        'property_type',
        'occupancy_type',
        'property_price',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'ssn_last_four',
        'employment_status',
        'employer_name',
        'job_title',
        'years_at_job',
        'annual_income',
        'credit_score_range',
        'has_bankruptcies',
        'bankruptcy_explanation',
        'selected_loan_details',
        'form_data',
        'documents_status',
        'property_details_snapshot',
        'timeline',
        'submitted_at',
        'last_update_at',
        'expected_decision_date',
        'assigned_officer_id',
        'officer_notes',
        'approval_details',
    ];

    protected $casts = [
        'has_bankruptcies' => 'boolean',
        'selected_loan_details' => 'array',
        'form_data' => 'array',
        'documents_status' => 'array',
        'property_details_snapshot' => 'array',
        'timeline' => 'array',
        'approval_details' => 'array',
        'submitted_at' => 'datetime',
        'last_update_at' => 'datetime',
        'expected_decision_date' => 'datetime',
        'date_of_birth' => 'date',
        'loan_amount' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'annual_income' => 'decimal:2',
        'property_price' => 'decimal:2',
        'interest_rate' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function offer()
    {
        return $this->belongsTo(PropertyOffer::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function assignedOfficer()
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }
    
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'documents_needed' => 'bg-blue-100 text-blue-800',
            'processing' => 'bg-purple-100 text-purple-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function create_user_activity($request, $property = null,$type="")
    {
        UserActivity::create([
            'user_id' => auth()->id(),
            'activity_type' => $type,
            'property_id' => $property->id,
            'inquiry_id' => null,
            'metadata' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'User activity recorded successfully',
            'success' => true
        ], 201);
    }
}
