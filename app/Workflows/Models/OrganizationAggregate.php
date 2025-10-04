<?php

namespace App\Workflows\Models;

use App\Models\Organization as EloquentOrganization;
use App\ValueObjects\OrganizationId;
use App\Models\OrganizationUser;
use App\Shared\Interfaces\AggregateRootInterface;

class OrganizationAggregate implements AggregateRootInterface
{
    private EloquentOrganization $organization;
    
    public function __construct(EloquentOrganization $organization)
    {
        $this->organization = $organization;
    }

    public function getId(): OrganizationId
    {
        return new OrganizationId($this->organization->id);
    }

    public function getName(): string
    {
        return $this->organization->name;
    }

    public function setName(string $name): void
    {
        $this->organization->name = $name;
        $this->organization->slug = \Illuminate\Support\Str::slug($name);
    }

    public function addMember(string $userId, string $role = 'member'): void
    {
        // Check if user is already a member
        $existingMembership = OrganizationUser::where([
            'organization_id' => $this->organization->id,
            'user_id' => $userId
        ])->first();

        if ($existingMembership) {
            throw new \DomainException('User is already a member of this organization');
        }

        OrganizationUser::create([
            'organization_id' => $this->organization->id,
            'user_id' => $userId,
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function removeMember(string $userId): void
    {
        // Prevent removing the owner
        if ($this->organization->owner_id === $userId) {
            throw new \DomainException('Cannot remove the owner from the organization');
        }

        OrganizationUser::where([
            'organization_id' => $this->organization->id,
            'user_id' => $userId
        ])->delete();
    }

    public function update(): bool
    {
        return $this->organization->save();
    }

    public function delete(): bool
    {
        return $this->organization->delete();
    }
}