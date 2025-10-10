<?php

namespace App\Services;

use App\DataTransferObjects\OrganizationData;
use App\Repositories\OrganizationRepository;
use App\Shared\Interfaces\ServiceInterface;
use App\Workflows\Commands\CreateOrganizationCommand;
use App\Workflows\Commands\CreateOrganizationCommandHandler;
use App\Workflows\Models\OrganizationAggregate;

class OrganizationApplicationService implements ServiceInterface
{
    public function __construct(
        private OrganizationRepository $repository,
        private EventStoreService $eventStore
    ) {}

    public function createOrganization(OrganizationData $data): OrganizationAggregate
    {
        $command = new CreateOrganizationCommand($data, $data->userId);
        $handler = new CreateOrganizationCommandHandler;
        $organization = $handler->handle($command);

        return new OrganizationAggregate($organization);
    }

    public function getOrganization(string $id): ?OrganizationAggregate
    {
        $organization = $this->repository->find($id);

        return $organization ? new OrganizationAggregate($organization) : null;
    }

    public function updateOrganization(OrganizationAggregate $aggregate, OrganizationData $data): OrganizationAggregate
    {
        $aggregate->setName($data->name);
        $aggregate->update();

        return $aggregate;
    }
}
