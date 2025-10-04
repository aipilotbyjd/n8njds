<?php

namespace App\Domains\Workflow\Services;

use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowVersion;
use App\Shared\Interfaces\ServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class WorkflowExecutionService implements ServiceInterface
{
    /**
     * Execute a workflow with given input data
     */
    public function executeWorkflow(Workflow $workflow, array $inputData = []): WorkflowExecution
    {
        // Validate that workflow can be executed
        if (!$workflow->isExecutable()) {
            throw new \Exception('Workflow is not executable');
        }

        // Create a new execution record
        $execution = WorkflowExecution::create([
            'execution_uuid' => (string) Str::uuid(),
            'workflow_id' => $workflow->uuid,
            'user_id' => auth()->id(), // Use authenticated user if available
            'status' => 'running',
            'started_at' => now(),
            'mode' => 'manual', // Could also be: trigger, scheduled
            'data' => $inputData,
            'node_executions' => [],
            'statistics' => [
                'start_time' => now()->toISOString(),
                'node_count' => count($workflow->nodes ?: []),
            ],
        ]);

        try {
            // Run the workflow execution
            $result = $this->runWorkflowExecution($workflow, $execution, $inputData);
            
            // Update execution with results
            $execution->update([
                'status' => 'success',
                'finished_at' => now(),
                'execution_time' => now()->diffInSeconds($execution->started_at),
                'data' => array_merge($execution->data ?: [], $result['data'] ?? []),
                'node_executions' => $result['node_executions'] ?? [],
                'statistics' => array_merge(
                    $execution->statistics ?: [], 
                    $result['statistics'] ?? [],
                    ['end_time' => now()->toISOString()]
                ),
            ]);
            
            // Update workflow stats
            $workflow->markAsExecuted();
        } catch (\Exception $e) {
            // Handle execution failure
            $execution->update([
                'status' => 'error',
                'finished_at' => now(),
                'execution_time' => now()->diffInSeconds($execution->started_at),
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);
            
            Log::error('Workflow execution failed', [
                'workflow_id' => $workflow->uuid,
                'execution_id' => $execution->execution_uuid,
                'error' => $e->getMessage(),
                'workflow_name' => $workflow->name,
            ]);
            
            throw $e;
        }

        return $execution;
    }

    /**
     * Run the actual workflow execution logic
     */
    protected function runWorkflowExecution(Workflow $workflow, WorkflowExecution $execution, array $inputData): array
    {
        $nodeExecutions = [];
        $executionStats = [
            'nodes_executed' => 0,
            'nodes_failed' => 0,
            'total_time' => 0,
        ];
        
        // Get the workflow definition
        $nodes = $workflow->nodes ?: [];
        $connections = $workflow->connections ?: [];
        
        // For now, we'll execute nodes in a simple manner
        // In a real implementation, you'd need to handle complex workflows with branching, parallel execution, etc.
        foreach ($nodes as $nodeId => $node) {
            try {
                $startTime = microtime(true);
                
                // Execute the node
                $nodeResult = $this->executeNode($node, $inputData, $execution);
                
                $executionTime = microtime(true) - $startTime;
                
                // Record node execution
                $nodeExecutions[$nodeId] = [
                    'node_id' => $nodeId,
                    'node_type' => $node['type'] ?? 'unknown',
                    'status' => 'success',
                    'input' => $inputData,
                    'output' => $nodeResult,
                    'execution_time' => $executionTime,
                    'executed_at' => now()->toISOString(),
                ];
                
                $executionStats['nodes_executed']++;
                $executionStats['total_time'] += $executionTime;
                
                // Update input data for next node with this node's output
                $inputData = $nodeResult;
                
            } catch (\Exception $e) {
                $nodeExecutions[$nodeId] = [
                    'node_id' => $nodeId,
                    'node_type' => $node['type'] ?? 'unknown',
                    'status' => 'error',
                    'input' => $inputData,
                    'error' => [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ],
                    'executed_at' => now()->toISOString(),
                ];
                
                $executionStats['nodes_failed']++;
                
                // For now, we'll continue with other nodes, but in a real system you might want to stop
                // depending on the workflow configuration and node failure handling
            }
        }
        
        return [
            'data' => $inputData,
            'node_executions' => $nodeExecutions,
            'statistics' => $executionStats,
        ];
    }

    /**
     * Execute a single node
     */
    protected function executeNode(array $node, array $inputData, WorkflowExecution $execution): array
    {
        // This is where you would call the appropriate node handler
        // based on the node type.
        
        $nodeType = $node['type'] ?? 'unknown';
        
        switch ($nodeType) {
            case 'http_request':
                return $this->executeHttpRequestNode($node, $inputData);
            case 'code':
                return $this->executeCodeNode($node, $inputData);
            case 'condition':
                return $this->executeConditionNode($node, $inputData);
            default:
                // For unknown node types, just return the input data
                return $inputData;
        }
    }

    /**
     * Execute an HTTP request node
     */
    protected function executeHttpRequestNode(array $node, array $inputData): array
    {
        // Extract node parameters
        $method = $node['parameters']['method'] ?? 'GET';
        $url = $node['parameters']['url'] ?? '';
        
        if (empty($url)) {
            throw new \Exception('HTTP request node requires a URL');
        }
        
        // Process URL with data substitution
        $url = $this->processTemplate($url, $inputData);
        
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders(
                $node['parameters']['headers'] ?? []
            )->{$method}($url, $node['parameters']['body'] ?? $inputData);
            
            return [
                'status' => $response->status(),
                'data' => $response->json(),
                'headers' => $response->headers(),
                'request_data' => $inputData,
            ];
        } catch (\Exception $e) {
            Log::error('HTTP request node failed', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
                'execution_id' => $execution->execution_uuid,
            ]);
            
            throw $e;
        }
    }

    /**
     * Execute a code node
     */
    protected function executeCodeNode(array $node, array $inputData): array
    {
        // Extract code
        $code = $node['parameters']['code'] ?? '';
        
        if (empty($code)) {
            throw new \Exception('Code node requires code to execute');
        }
        
        // In a real implementation, you'd run the code in a sandboxed environment
        // For security reasons, actual code execution should be carefully controlled
        // For now, we'll simulate the execution:
        return [
            'output' => 'Executed code node',
            'input_data' => $inputData,
            'result' => $inputData, // In real implementation, this would be actual code execution result
        ];
    }

    /**
     * Execute a condition node
     */
    protected function executeConditionNode(array $node, array $inputData): array
    {
        // Extract condition parameters
        $condition = $node['parameters']['condition'] ?? '';
        
        // In a real implementation, evaluate the condition properly
        // For now, we return a default result
        return [
            'condition_met' => true, // In real implementation, evaluate the condition
            'input_data' => $inputData,
        ];
    }

    /**
     * Process a template string with data substitution
     */
    protected function processTemplate(string $template, array $data): string
    {
        // More sophisticated template processing
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $template = str_replace('{{' . $key . '}}', json_encode($value), $template);
            } else {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }
        }
        
        return $template;
    }

    /**
     * Schedule a workflow for execution
     */
    public function scheduleWorkflow(Workflow $workflow, array $inputData = [], string $schedule = null): WorkflowExecution
    {
        // Create an execution with scheduled status
        return WorkflowExecution::create([
            'execution_uuid' => (string) Str::uuid(),
            'workflow_id' => $workflow->uuid,
            'user_id' => auth()->id(),
            'status' => 'scheduled',
            'mode' => 'scheduled',
            'data' => $inputData,
            'node_executions' => [],
            'statistics' => [
                'scheduled_time' => $schedule ?: now()->addMinutes(1)->toISOString(),
            ],
        ]);
    }

    /**
     * Cancel a running execution
     */
    public function cancelExecution(WorkflowExecution $execution): bool
    {
        if ($execution->status === 'running') {
            $execution->update([
                'status' => 'canceled',
                'finished_at' => now(),
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Get execution statistics for a workflow
     */
    public function getExecutionStats(Workflow $workflow): array
    {
        $executions = $workflow->executions();
        
        return [
            'total_executions' => $executions->count(),
            'successful_executions' => $executions->where('status', 'success')->count(),
            'failed_executions' => $executions->where('status', 'error')->count(),
            'canceled_executions' => $executions->where('status', 'canceled')->count(),
            'average_execution_time' => $executions->avg('execution_time') ?? 0,
            'last_execution' => $executions->latest()->first(),
        ];
    }
}