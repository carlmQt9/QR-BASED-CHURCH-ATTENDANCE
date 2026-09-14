<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'membership_group',
        'approval_status',
        'approved_at',
        'member_code',
        'qr_token',
        'password',
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

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Get membership group display text, handling missing column gracefully
     */
    public function getMembershipGroupDisplayAttribute()
    {
        // Check if we have the membership_group attribute
        if (isset($this->attributes['membership_group']) && !empty($this->attributes['membership_group'])) {
            return $this->attributes['membership_group'];
        }
        
        // Fallback based on role
        return match($this->role) {
            'admin' => 'Admin',
            'leader' => 'Leader', 
            default => 'Member'
        };
    }

    /**
     * Get membership group, handling cases where column doesn't exist
     */
    public function getMembershipGroupAttribute($value)
    {
        // If the column doesn't exist or is null, return null gracefully
        return $value;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
