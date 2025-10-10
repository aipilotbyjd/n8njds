<?php

namespace App\Workflows\Commands;

use App\Shared\Interfaces\QueryInterface;

class GetOrganizationQuery implements QueryInterface
{
    public function __construct(
        public readonly int $organizationId,
        public readonly string $userId
    ) {}
}
