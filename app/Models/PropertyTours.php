<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyTours extends Model
{
    protected $table = 'property_tours';

    protected $fillable = [
        'user_id',
        'property_id',
        'agent_id',
        'date',
        'time',
        'meeting_type',
        'notes',
        'buyer_id',
        'scheduled_at',
        'status',
        'is_virtual',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
    
}
