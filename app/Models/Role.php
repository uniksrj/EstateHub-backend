<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'level',
        'is_default',
        'is_system',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function users(){
        return $this->hasMany(User::class);
    }

    public function permission(){
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function scopeSystem($query){
        return $query->where('is_system', true);
    }

    public function scopeDefault($query){
        return $query->where('is_default', true);
    }

    public function hasPermission($permission){
        if(is_string($permission)){
            return $this->permission()->where('name', $permission)->exists();
        }
        return false;
    }
    
}
