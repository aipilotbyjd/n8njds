<?php

namespace App\Workflows\Commands;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Shared\Interfaces\CommandHandlerInterface;
use App\Shared\Interfaces\CommandInterface;
use App\Workflows\Events\OrganizationCreated;
use Illuminate\Support\Str;

class CreateOrganizationCommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command)
    {
        if (! $command instanceof CreateOrganizationCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        $organization = Organization::create([
            'name' => $command->data->name,
            'slug' => Str::slug($command->data->name),
            'description' => $command->data->description,
            'owner_id' => $command->userId,
            'timezone' => $command->data->timezone,
            'settings' => $command->data->settings,
        ]);

        // Add the user as an owner to the organization
        OrganizationUser::create([
            'organization_id' => $organization->id,
            'user_id' => $command->userId,
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Dispatch domain event
        OrganizationCreated::dispatch(
            $organization->id,
            $organization->name,
            $command->userId
        );

        return $organization;
    }
}
