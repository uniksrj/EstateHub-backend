<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InquiryResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'sender_type',
        'sender_id',
        'message',
        'attachments',
        'is_read'
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['sender_name', 'timestamp'];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

     public function getSenderNameAttribute()
    {
        return $this->sender ? $this->sender->name : 'Unknown';
    }

    public function getTimestampAttribute()
    {
        return $this->created_at;
    }

    public function scopeFromSeller($query)
    {
        return $query->where("sender_type", "seller");
    }

    public function scopeFromBuyer($query)
    {
        return $query->where("sender_type", "buyer");
    }

    public function scopeUnread($query)
    {
        return $query->where("is_read", false);
    }

    public function markAsRead()
    {
        return $this->update(["is_read", true]);
    }

    public function isFromSeller()
    {
        return $this->sender_type === "seller";
    }

    public function isFromBuyer(): bool
    {
        return $this->sender_type === 'buyer';
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y g:i A');
    }
}
