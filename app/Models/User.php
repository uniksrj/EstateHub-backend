<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role_id',
        'bio',
        'is_active',
        'is_active',
        'is_verified',
        'timezone',
        'settings',
        'deleted_at',
        'last_login_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'settings' => 'array',
        'last_login_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo((Role::class));
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }


    public function properties()
    {
        return $this->hasMany(Property::class, 'agent_id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->role->name === $role;
        }

        if (is_array($role)) {
            return in_array($this->role->name, $role);
        }
        return false;
    }

    public function hasPermission($permission)
    {
        if ($this->role && $this->role->permissions) {
            return $this->role->permissions->contains('name', $permission);
        }
        return false;
    }

    public function canAccess($permission)
    {
        return $this->hasPermission($permission);
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isAgent()
    {
        return $this->hasRole('agent');
    }

    public function isBuyer()
    {
        return $this->hasRole('buyer');
    }

    public function isSeller()
    {
        return $this->hasRole('seller');
    }

    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function activitys()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function recentActivities($limit = 10)
    {
        return $this->activitys()
            ->with(['property', 'inquiry'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function propertyViews()
    {
        return $this->hasMany(PropertyView::class);
    }

    public function purchases()
    {
        return $this->hasMany(PropertyPurchase::class, 'buyer_id');
    }
}
