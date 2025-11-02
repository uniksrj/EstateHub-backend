<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealLoss extends Model
{
    protected $fillable = [
        'property_id',
        'buyer_id',
        'agent_id',
        'reason',
        'details',
        'lost_at'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
