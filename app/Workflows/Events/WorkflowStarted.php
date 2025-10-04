<?php

namespace App\Workflows\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class WorkflowStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $executionId,
        public string $workflowId
    ) {
    }
}