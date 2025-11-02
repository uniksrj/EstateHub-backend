<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealPipeline extends Model
{
    protected $table = 'deal_pipeline';

    protected $fillable = [
        'property_id',
        'agent_id',
        'buyer_id',
        'status',
        'offer_price',
        'notes',
        'loss_id'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function lossReason()
    {
        return $this->belongsTo(DealLoss::class, 'loss_id');
    }
}
