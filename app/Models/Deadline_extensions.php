<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deadline_extensions extends Model
{
   use HasFactory;

   protected $fillable = [
       'deal_id',
       'extended_by',
       'extension_date',
       'reason',
       'extension_type',
       'extension_days',
         'old_deadline',
            'new_deadline'
   ];

   protected $dates = [
       'extension_date',
       'created_at',
       'updated_at'
   ];

   protected $table = 'deadline_extensions';

    public function deal()
    {
         return $this->belongsTo(Deal::class, 'deal_id');
    }
    public function user()
    {
         return $this->belongsTo(User::class, 'extended_by');
    }



}
