<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    protected $table = 'user_activities';

    protected $fillable = [
        'user_id',
        'activity_type',
        'property_id',
        'inquiry_id',
        'metadata',
        'ip_address',
        'user_agent'
    ];

     protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
    public function inquiry(){
        return $this->belongsTo(Inquiry::class);
    }
}
