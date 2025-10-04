<?php

namespace App\Repositories;

use App\Models\Organization;

class OrganizationRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Organization();
    }

    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function findByOwnerId(string $ownerId)
    {
        return $this->model->where('owner_id', $ownerId)->get();
    }

    public function getUserOrganizations(string $userId)
    {
        return $this->model
            ->join('organization_user', 'organizations.id', '=', 'organization_user.organization_id')
            ->where('organization_user.user_id', $userId)
            ->select('organizations.*')
            ->get();
    }
}