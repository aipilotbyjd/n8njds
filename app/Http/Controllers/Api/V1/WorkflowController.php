<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Workflows\Executions\AdvancedWorkflowExecutionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WorkflowController extends Controller
{
    public function __construct(
        private AdvancedWorkflowExecutionService $executionService
    ) {
    }

    /**
     * Display a listing of the workflows.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $workflows = $user->workflows()
            ->with(['user', 'organization'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['workflows' => $workflows]);
    }

    /**
     * Store a newly created workflow.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'definition' => 'required|array',
            'is_active' => 'boolean',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $workflow = Workflow::create([
            'name' => $request->name,
            'definition' => $request->definition,
            'is_active' => $request->is_active ?? false,
            'user_id' => $request->user()->id,
            'organization_id' => $request->organization_id ?? $request->user()->current_organization_id,
        ]);

        return response()->json([
            'message' => 'Workflow created successfully',
            'workflow' => $workflow->load(['user', 'organization'])
        ], 201);
    }

    /**
     * Display the specified workflow.
     */
    public function show(Workflow $workflow, Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has access to this workflow
        if ($workflow->user_id !== $user->id && 
            ($workflow->organization_id && !$user->organizations()->where('organization_id', $workflow->organization_id)->exists())) {
            return response()->json([
                'message' => 'Unauthorized to access this workflow'
            ], 403);
        }

        return response()->json([
            'workflow' => $workflow->load(['user', 'organization'])
        ]);
    }

    /**
     * Update the specified workflow.
     */
    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $user = $request->user();
        
        // Check if user owns the workflow
        if ($workflow->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to update this workflow'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'definition' => 'sometimes|required|array',
            'is_active' => 'boolean',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $workflow->update([
            'name' => $request->name ?? $workflow->name,
            'definition' => $request->definition ?? $workflow->definition,
            'is_active' => $request->is_active ?? $workflow->is_active,
            'organization_id' => $request->organization_id ?? $workflow->organization_id,
        ]);

        return response()->json([
            'message' => 'Workflow updated successfully',
            'workflow' => $workflow->load(['user', 'organization'])
        ]);
    }

    /**
     * Remove the specified workflow.
     */
    public function destroy(Workflow $workflow, Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user owns the workflow
        if ($workflow->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to delete this workflow'
            ], 403);
        }

        $workflow->delete();

        return response()->json([
            'message' => 'Workflow deleted successfully'
        ]);
    }

    /**
     * Get executions for this workflow.
     */
    public function executions(Workflow $workflow, Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has access to this workflow
        if ($workflow->user_id !== $user->id && 
            ($workflow->organization_id && !$user->organizations()->where('organization_id', $workflow->organization_id)->exists())) {
            return response()->json([
                'message' => 'Unauthorized to access this workflow'
            ], 403);
        }

        $executions = $workflow->executions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['executions' => $executions]);
    }

    /**
     * Execute this workflow.
     */
    public function execute(Workflow $workflow, Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has access to this workflow
        if ($workflow->user_id !== $user->id && 
            ($workflow->organization_id && !$user->organizations()->where('organization_id', $workflow->organization_id)->exists())) {
            return response()->json([
                'message' => 'Unauthorized to execute this workflow'
            ], 403);
        }

        if (!$workflow->is_active) {
            return response()->json([
                'message' => 'Cannot execute an inactive workflow'
            ], 400);
        }

        $input = $request->input('input', []);

        // Execute workflow asynchronously
        $execution = $this->executionService->executeWorkflow($workflow, $input, $user->id);

        return response()->json([
            'message' => 'Workflow execution started',
            'execution' => $execution
        ], 202);
    }

    /**
     * Get nodes for this workflow.
     */
    public function nodes(Workflow $workflow, Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has access to this workflow
        if ($workflow->user_id !== $user->id && 
            ($workflow->organization_id && !$user->organizations()->where('organization_id', $workflow->organization_id)->exists())) {
            return response()->json([
                'message' => 'Unauthorized to access this workflow'
            ], 403);
        }

        $nodes = $workflow->definition['nodes'] ?? [];

        return response()->json(['nodes' => $nodes]);
    }
}
