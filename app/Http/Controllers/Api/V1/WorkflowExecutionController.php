<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WorkflowExecution;
use Illuminate\Http\Request;

class WorkflowExecutionController extends Controller
{
    public function index(Request $request)
    {
        $executions = WorkflowExecution::where('user_id', $request->user()->id)
            ->with(['workflow', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['executions' => $executions]);
    }

    public function show(Request $request, WorkflowExecution $execution)
    {
        // Check if user has access to this execution
        if ($execution->user_id !== $request->user()->id && $execution->workflow->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['execution' => $execution->load(['workflow', 'user'])]);
    }

    public function store(Request $request)
    {
        // This would typically be handled by the workflow engine
        return response()->json(['message' => 'Use workflow execute endpoint instead'], 400);
    }

    public function update(Request $request, WorkflowExecution $execution)
    {
        return response()->json(['message' => 'Executions are read-only'], 400);
    }

    public function destroy(Request $request, WorkflowExecution $execution)
    {
        // Check if user has access to this execution
        if ($execution->user_id !== $request->user()->id && $execution->workflow->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $execution->delete();

        return response()->json(['message' => 'Execution deleted successfully']);
    }

    public function logs(Request $request, WorkflowExecution $execution)
    {
        // Check if user has access to this execution
        if ($execution->user_id !== $request->user()->id && $execution->workflow->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // For now, return the execution data as logs
        // In a real implementation, this would return detailed logs
        return response()->json([
            'execution_id' => $execution->id,
            'logs' => $execution->node_executions ?? [],
            'status' => $execution->status,
            'started_at' => $execution->started_at,
            'finished_at' => $execution->finished_at,
        ]);
    }
}