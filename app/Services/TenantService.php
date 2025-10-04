<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantService
{
    public function createTenant(string $name): void
    {
        Tenant::create([
            'name' => $name,
            'uuid' => Str::uuid(),
        ]);
    }
}
