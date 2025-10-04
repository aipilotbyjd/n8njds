<?php

namespace App\Jobs\Workflows;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\WorkflowExecution;
use App\Workflows\Executions\WorkflowEngine;

class ExecuteWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout

    public function __construct(
        public string $executionId,
        public array $input
    ) {
    }

    public function handle(): void
    {
        $execution = WorkflowExecution::where('execution_uuid', $this->executionId)->first();
        
        if (!$execution) {
            return; // Execution might have been deleted
        }

        // Update status to running
        $execution->update(['status' => 'running']);

        try {
            $workflow = $execution->workflow;
            
            if (!$workflow || !$workflow->is_active) {
                $execution->update([
                    'status' => 'error',
                    'error' => ['message' => 'Workflow is not active or does not exist'],
                ]);
                return;
            }

            $engine = new WorkflowEngine();
            $result = $engine->execute($workflow, $this->input);

            // The execution is already updated by the engine, but we might need to update it again
            if ($result['status'] === 'error') {
                $execution->update([
                    'status' => 'error',
                    'error' => ['message' => $result['error']],
                ]);
            } else {
                $execution->update([
                    'status' => 'success',
                ]);
            }
        } catch (\Exception $e) {
            $execution->update([
                'status' => 'error',
                'error' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);
        }
    }
}