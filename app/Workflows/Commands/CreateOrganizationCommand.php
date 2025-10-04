<?php

namespace App\Workflows\Commands;

use App\Shared\Interfaces\CommandInterface;
use App\DataTransferObjects\OrganizationData;

class CreateOrganizationCommand implements CommandInterface
{
    public function __construct(
        public readonly OrganizationData $data,
        public readonly string $userId
    ) {
    }
}