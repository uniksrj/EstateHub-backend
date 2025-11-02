<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyPurchase extends Model
{
    protected $fillable = [
        'property_id',
        'buyer_id',
        'sale_price',
        'commission',
        'taxes',
        'fees',
        'net_amount',
        'purchase_date',
        'status'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
