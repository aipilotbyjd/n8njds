<?php

namespace App\Workflows\Executions;

use App\Jobs\Workflows\ExecuteWorkflowJob;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Services\EventStoreService;
use Illuminate\Support\Facades\Queue;

class AdvancedWorkflowExecutionService
{
    public function __construct(
        private EventStoreService $eventStore
    ) {
    }

    public function executeWorkflow(Workflow $workflow, array $input = [], string $userId = null): WorkflowExecution
    {
        // Create a new execution record
        $execution = WorkflowExecution::create([
            'workflow_id' => $workflow->id,
            'user_id' => $userId,
            'status' => 'pending',
            'data' => $input,
            'mode' => 'trigger', // or 'manual', 'scheduled'
        ]);

        // Dispatch a job to process the execution in the background
        ExecuteWorkflowJob::dispatch($execution->id, $input);

        return $execution;
    }

    public function executeWorkflowNow(Workflow $workflow, array $input = [], string $userId = null): array
    {
        $engine = new WorkflowEngine();
        return $engine->execute($workflow, $input);
    }

    public function getExecutionStatus(string $executionId): ?array
    {
        $execution = WorkflowExecution::where('execution_uuid', $executionId)->first();
        
        if (!$execution) {
            return null;
        }

        return [
            'id' => $execution->id,
            'execution_uuid' => $execution->execution_uuid,
            'status' => $execution->status,
            'started_at' => $execution->started_at,
            'finished_at' => $execution->finished_at,
            'execution_time' => $execution->execution_time,
            'error' => $execution->error,
            'data' => $execution->data,
        ];
    }

    public function getWorkflowExecutions(Workflow $workflow): array
    {
        return WorkflowExecution::where('workflow_id', $workflow->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->toArray();
    }
}