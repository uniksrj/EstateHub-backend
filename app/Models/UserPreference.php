<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'preferred_location', 
        'min_budget',
        'max_budget',
        'property_type',
        'bedrooms',
        'bathrooms', 
        'move_in_timeline',
        'newsletter',
        'selling_timeline',
        'property_address',
        'license_number',
        'agency_name'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'min_budget' => 'decimal:2',
        'max_budget' => 'decimal:2',
        'newsletter' => 'boolean',        
    ];

    /**
     * Get the user that owns the preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
