<?php

namespace App\Listeners;

use App\Events\WorkflowExecuted;

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
