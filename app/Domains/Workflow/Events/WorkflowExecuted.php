<?php

namespace App\Events;

use App\Models\WorkflowExecution;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowExecuted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkflowExecution $execution,
        public float $executionTime,
        public string $status
    ) {}
}
