<?php

namespace App\Services;

use App\Models\User;
use App\Models\Organization;
use App\Shared\Interfaces\ServiceInterface;

class UserAuthorizationService implements ServiceInterface
{
    public function canAccessOrganization(User $user, Organization $organization): bool
    {
        return $user->organizations()->where('organization_id', $organization->id)->exists();
    }

    public function canManageOrganization(User $user, Organization $organization): bool
    {
        // Check if user is owner or admin of the organization
        $membership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->first();
            
        return $membership && in_array($membership->role, ['owner', 'admin']);
    }

    public function canDeleteOrganization(User $user, Organization $organization): bool
    {
        // Only the owner can delete the organization
        return $organization->owner_id === $user->id;
    }

    public function isOwner(User $user, Organization $organization): bool
    {
        return $organization->owner_id === $user->id;
    }

    public function hasRole(User $user, Organization $organization, array $roles): bool
    {
        $membership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->first();
            
        return $membership && in_array($membership->role, $roles);
    }
}