<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BetaFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'environment',
        'page_url',
        'issue_link',
        'message',
        'screenshot_url',
        'screenshot_public_id',
        'status',
        'user_agent',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
