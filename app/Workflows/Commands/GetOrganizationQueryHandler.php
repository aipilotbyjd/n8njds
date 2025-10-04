<?php

namespace App\Workflows\Commands;

use App\Models\Organization;
use App\Shared\Interfaces\QueryHandlerInterface;
use App\Shared\Interfaces\QueryInterface;

class GetOrganizationQueryHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query)
    {
        if (!$query instanceof GetOrganizationQuery) {
            throw new \InvalidArgumentException('Invalid query type');
        }

        // Check if user has access to this organization
        $membership = \App\Models\User::find($query->userId)
            ->organizationMemberships()
            ->where('organization_id', $query->organizationId)
            ->first();
            
        if (!$membership) {
            return null;
        }

        return Organization::find($query->organizationId);
    }
}