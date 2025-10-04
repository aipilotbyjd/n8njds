<?php

namespace App\Console\Commands;

use App\Domains\Billing\Services\TenantService;
use Illuminate\Console\Command;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create {name}';
    protected $description = 'Create a new tenant';

    public function __construct(private readonly TenantService $tenantService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $name = $this->argument('name');
        $this->tenantService->createTenant($name);
        $this->info("Tenant '{$name}' created successfully.");
    }
}
