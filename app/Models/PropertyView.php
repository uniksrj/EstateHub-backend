<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyView extends Model
{
     protected $fillable = ['user_id', 'property_id', 'view_duration', 'view_source'];

     protected $casts = [
        'viewed_at' => 'datetime'
    ];

     public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function property() {
        return $this->belongsTo(Property::class);
    }
}
