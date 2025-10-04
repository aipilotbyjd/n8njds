<?php

namespace App\Workflows\Executions;

use App\Workflows\Nodes\NodeFactory;
use App\Workflows\Nodes\NodeInterface;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Workflows\Events\WorkflowStarted;
use App\Workflows\Events\WorkflowCompleted;
use App\Workflows\Events\WorkflowFailed;

class WorkflowEngine
{
    public function execute(Workflow $workflow, array $input = [], string $executionId = null): array
    {
        if ($executionId) {
            $execution = WorkflowExecution::where('execution_uuid', $executionId)->first();
            if (!$execution) {
                return [
                    'status' => 'error',
                    'error' => 'Execution not found',
                ];
            }
        } else {
            $execution = WorkflowExecution::create([
                'execution_uuid' => (string) \Illuminate\Support\Str::uuid(),
                'workflow_id' => $workflow->id,
                'status' => 'running',
                'started_at' => now(),
            ]);
        }

        WorkflowStarted::dispatch($execution->id, $workflow->id);

        try {
            $nodes = $workflow->definition['nodes'] ?? [];
            $connections = $workflow->definition['connections'] ?? [];
            
            $nodeInstances = [];
            foreach ($nodes as $nodeData) {
                $node = NodeFactory::create(
                    $nodeData['type'],
                    $nodeData['id'],
                    $nodeData['name'],
                    $nodeData['parameters'] ?? []
                );
                
                if ($node) {
                    $nodeInstances[$nodeData['id']] = $node;
                }
            }

            // Execute nodes in order
            $output = $this->executeNodes($nodeInstances, $connections, $input);
            
            $execution->update([
                'status' => 'success',
                'finished_at' => now(),
                'data' => $output,
                'execution_time' => now()->diffInSeconds($execution->started_at),
            ]);

            WorkflowCompleted::dispatch($execution->id, $workflow->id);

            return [
                'status' => 'success',
                'execution_id' => $execution->execution_uuid,
                'output' => $output,
            ];
        } catch (\Exception $e) {
            $execution->update([
                'status' => 'error',
                'finished_at' => now(),
                'error' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
                'execution_time' => now()->diffInSeconds($execution->started_at),
            ]);

            WorkflowFailed::dispatch($execution->id, $workflow->id, $e->getMessage());

            return [
                'status' => 'error',
                'execution_id' => $execution->execution_uuid,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function executeNodes(array $nodeInstances, array $connections, array $input): array
    {
        $results = [];
        $output = $input;

        // For now, execute nodes in the order they are defined
        // In a more advanced implementation, we would respect the graph structure
        foreach ($nodeInstances as $nodeId => $node) {
            $nodeOutput = $node->execute($output);
            $results[$nodeId] = $nodeOutput;
            $output = $nodeOutput['data'] ?? $output; // Pass the data from one node to the next
        }

        return [
            'results' => $results,
            'final_output' => $output,
        ];
    }
}