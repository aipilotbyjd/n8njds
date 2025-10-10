<?php

namespace App\Shared\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface SpecificationInterface
{
    public function isSatisfiedBy(mixed $model): bool;

    public function toQuery(): Builder;
}
