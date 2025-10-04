<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the organizations that the user owns.
     */
    public function ownedOrganizations()
    {
        return $this->hasMany(\App\Models\Organization::class, 'owner_id');
    }

    /**
     * Get the organizations that the user belongs to.
     */
    public function organizations()
    {
        return $this->belongsToMany(
            \App\Models\Organization::class,
            'organization_user'
        )->withPivot('role', 'is_active', 'joined_at')->withTimestamps();
    }

    /**
     * Get the user's current organization.
     */
    public function currentOrganization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'current_organization_id');
    }

    /**
     * Get the user's membership in organizations.
     */
    public function organizationMemberships()
    {
        return $this->hasMany(\App\Models\OrganizationUser::class);
    }
}
