<?php

namespace App\Domains\Workflow\Providers;

use App\Domains\Workflow\Repositories\WorkflowRepository;
use App\Domains\Workflow\Repositories\WorkflowRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WorkflowRepositoryInterface::class,
            WorkflowRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}