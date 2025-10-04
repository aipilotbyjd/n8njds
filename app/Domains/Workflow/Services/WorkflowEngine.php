<?php

namespace App\Workflows\Executions;

use App\Jobs\Workflows\ExecuteWorkflowNodeJob;
use App\Models\Workflow;

class WorkflowEngine
{
    public function execute(Workflow $workflow): void
    {
        $definition = $workflow->definition;
        $nodes = $definition['nodes'] ?? [];
        $connections = $definition['connections'] ?? [];

        $nodeMap = [];
        foreach ($nodes as $nodeData) {
            $nodeMap[$nodeData['id']] = $nodeData;
        }

        $allNodes = array_keys($nodeMap);
        $targetNodes = array_column($connections, 'target');
        $triggerNodes = array_diff($allNodes, $targetNodes);

        $execution = $workflow->executions()->create([
            'status' => 'pending',
        ]);

        foreach ($triggerNodes as $triggerNodeId) {
            ExecuteWorkflowNodeJob::dispatch($execution->id, $triggerNodeId, []);
        }
    }
}
