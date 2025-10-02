<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Favorite extends Model
{
    use HasFactory, Notifiable;
    const UPDATED_AT = null;
    protected $fillable = [
        'user_id', 'property_id', 
    ];

    protected $hidden = [
        'created_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'property_id' => 'integer',
    ];

    public function properties(){
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function getCollection(){
        return $this->belongsTo(Property::class, 'property_id');
    }

     public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function favorites(){
        return $this->hasMany(Favorite::class);
    }

    public function isAdmin(){
        return $this->role === 'admin';
    }

     public function isAgent() {
        return $this->role === 'agent';
    }

}
