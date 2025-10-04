<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
        'timezone',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Get the owner of the organization.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the users that belong to the organization.
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'organization_user'
        )->withPivot('role', 'is_active', 'joined_at')->withTimestamps();
    }

    /**
     * Get the workflows for the organization.
     */
    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }
}