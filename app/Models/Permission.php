<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'group',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function roles(){
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function scopeSystem($query){
        return $query->where('is_system', true);
    }

    public function scopeGroup($query, $group){
        return $query->where('group', $group);
    }
}
