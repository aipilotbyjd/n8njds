<?php

namespace App\Listeners;

use App\Events\WorkflowExecuted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogWorkflowExecution
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WorkflowExecuted $event): void
    {
        //
    }
}
