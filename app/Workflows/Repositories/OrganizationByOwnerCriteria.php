<?php

namespace App\Workflows\Repositories;

use App\Shared\Interfaces\CriteriaInterface;
use Illuminate\Database\Eloquent\Builder;

class OrganizationByOwnerCriteria implements CriteriaInterface
{
    public function __construct(
        private string $ownerId
    ) {}

    public function apply(Builder $query): Builder
    {
        return $query->where('owner_id', $this->ownerId);
    }
}
