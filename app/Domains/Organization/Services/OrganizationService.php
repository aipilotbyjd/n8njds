<?php

namespace App\Domains\Organization\Services;

use App\Domains\Organization\DataTransferObjects\OrganizationData;
use App\Domains\Organization\Repositories\OrganizationRepository;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Shared\Interfaces\ServiceInterface;
use Illuminate\Support\Str;

class OrganizationService implements ServiceInterface
{
    public function __construct(
        private OrganizationRepository $repository
    ) {}

    public function create(OrganizationData $data): Organization
    {
        $organization = $this->repository->create([
            'name' => $data->name,
            'slug' => Str::slug($data->name),
            'description' => $data->description,
            'owner_id' => $data->userId,
            'timezone' => $data->timezone,
            'settings' => $data->settings,
        ]);

        // Add the user as an owner to the organization
        OrganizationUser::create([
            'organization_id' => $organization->id,
            'user_id' => $data->userId,
            'role' => 'owner',
            'is_active' => true,
        ]);

        return $organization;
    }

    public function update(Organization $organization, OrganizationData $data): Organization
    {
        $organization->update([
            'name' => $data->name,
            'slug' => Str::slug($data->name),
            'description' => $data->description,
            'timezone' => $data->timezone,
            'settings' => $data->settings,
        ]);

        return $organization;
    }

    public function delete(Organization $organization): bool
    {
        return $organization->delete();
    }
}
