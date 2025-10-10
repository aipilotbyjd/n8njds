<?php

namespace App\Workflows\Commands;

use App\DataTransferObjects\OrganizationData;
use App\Shared\Interfaces\CommandInterface;

class CreateOrganizationCommand implements CommandInterface
{
    public function __construct(
        public readonly OrganizationData $data,
        public readonly string $userId
    ) {}
}
