<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'license_number',
        'bio',
        'website',
        'social_links',
        'notification_preferences',
    ];

    protected $casts = [
        'social_links' => 'array',
        'notification_preferences' => 'array',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
