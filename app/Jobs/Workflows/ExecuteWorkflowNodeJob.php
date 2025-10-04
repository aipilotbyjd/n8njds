<?php

namespace App\Jobs\Workflows;

use App\Models\WorkflowExecution;
use App\Workflows\Nodes\NodeFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteWorkflowNodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $workflowExecutionId,
        public string $nodeId,
        public array $input
    ) {
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }
    public function handle(): void
    {
        $execution = WorkflowExecution::findOrFail($this->workflowExecutionId);

        $nodeExecutions = $execution->node_executions ?? [];
        $nodeExecutions[$this->nodeId] = ['status' => 'running', 'started_at' => now()];
        $execution->update(['node_executions' => $nodeExecutions]);

        $workflow = $execution->workflow;
        $definition = $workflow->definition;
        $nodes = $definition['nodes'] ?? [];
        $connections = $definition['connections'] ?? [];

        $nodeData = null;
        foreach ($nodes as $n) {
            if ($n['id'] === $this->nodeId) {
                $nodeData = $n;
                break;
            }
        }

        if (!$nodeData) {
            $nodeExecutions[$this->nodeId]['status'] = 'error';
            $nodeExecutions[$this->nodeId]['error'] = 'Node not found';
            $execution->update(['node_executions' => $nodeExecutions]);
            return;
        }

        $node = NodeFactory::create($nodeData['type'], $nodeData['id'], $nodeData['name'], $nodeData['parameters'] ?? []);

        if ($node) {
            try {
                $result = $node->execute($this->input);
                $output = $result['data'];

                $nodeExecutions[$this->nodeId]['status'] = 'success';
                $nodeExecutions[$this->nodeId]['finished_at'] = now();
                $execution->update(['node_executions' => $nodeExecutions]);

                $adjacencyList = [];
                foreach ($connections as $connection) {
                    $source = $connection['source'];
                    $target = $connection['target'];
                    $sourceHandle = $connection['source_handle'] ?? null;
                    if (!isset($adjacencyList[$source])) {
                        $adjacencyList[$source] = [];
                    }
                    $adjacencyList[$source][] = ['target' => $target, 'source_handle' => $sourceHandle];
                }

                if (isset($adjacencyList[$this->nodeId])) {
                    foreach ($adjacencyList[$this->nodeId] as $nextConnection) {
                        $nextNodeId = $nextConnection['target'];
                        $sourceHandle = $nextConnection['source_handle'];

                        if ($nodeData['type'] === 'if') {
                            $conditionResult = $result['condition_result'] ? 'true' : 'false';
                            if ($sourceHandle === $conditionResult) {
                                self::dispatch($this->workflowExecutionId, $nextNodeId, $output);
                            }
                        } elseif ($nodeData['type'] === 'switch') {
                            $outputBranch = $result['output_branch'];
                            if ($sourceHandle === $outputBranch) {
                                self::dispatch($this->workflowExecutionId, $nextNodeId, $output);
                            }
                        } elseif ($nodeData['type'] === 'split') {
                            if (isset($result['is_split']) && $result['is_split']) {
                                foreach ($result['data'] as $item) {
                                    self::dispatch($this->workflowExecutionId, $nextNodeId, $item);
                                }
                            }
                        } else {
                            self::dispatch($this->workflowExecutionId, $nextNodeId, $output);
                        }
                    }
                }
            } catch (\Exception $e) {
                $nodeExecutions[$this->nodeId]['status'] = 'error';
                $nodeExecutions[$this->nodeId]['error'] = $e->getMessage();
                $execution->update(['node_executions' => $nodeExecutions]);
                $this->fail($e);
            }
        }
    }
}
