<?php

namespace App\Domains\Workflow\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Domains\Workflow\Http\Resources\WorkflowResource;
use App\Domains\Workflow\Http\Resources\WorkflowVersionResource;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use App\Domains\Workflow\Services\WorkflowExecutionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkflowController extends Controller
{
    public function __construct(
        private WorkflowExecutionService $executionService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Workflow::query();
        
        // Filter by organization if user is part of one
        if ($request->user()->currentOrganization) {
            $query->where('organization_id', $request->user()->currentOrganization->id);
        }
        
        // Apply status filter if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Apply search if provided
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        $workflows = $query->with(['organization', 'creator', 'executions'])->paginate(
            $request->get('per_page', 15)
        );
        
        return WorkflowResource::collection($workflows);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'nodes' => 'nullable|array',
            'connections' => 'nullable|array',
            'settings' => 'nullable|array',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);
        
        $validator->validate(); // This throws an exception on failure
        
        $workflow = new Workflow($validator->validated());
        $workflow->created_by = $request->user()->id;
        $workflow->updated_by = $request->user()->id;
        
        // Set organization if not provided and user has a current organization
        if (!$workflow->organization_id && $request->user()->currentOrganization) {
            $workflow->organization_id = $request->user()->currentOrganization->id;
        }
        
        $workflow->save();
        
        // Create the initial version
        $workflow->createVersion($validator->validated(), $request->user()->id, 'Initial version');
        
        return (new WorkflowResource($workflow->load(['organization', 'creator', 'executions'])))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Workflow $workflow): WorkflowResource
    {
        // Check if user has access to this workflow
        $this->authorizeWorkflowAccess($workflow);
        
        return new WorkflowResource($workflow->load(['organization', 'creator', 'executions', 'versions', 'currentVersion']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Workflow $workflow): WorkflowResource
    {
        // Check if user has access to this workflow
        $this->authorizeWorkflowAccess($workflow);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive', 'draft'])],
            'nodes' => 'nullable|array',
            'connections' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);
        
        $validator->validate(); // This throws an exception on failure
        
        $workflow->update(array_merge(
            $validator->validated(),
            ['updated_by' => $request->user()->id]
        ));
        
        // Create a new version if significant fields changed
        if (array_intersect_key($validator->validated(), array_flip(['name', 'nodes', 'connections', 'settings']))) {
            $workflow->createVersion(
                $validator->validated(), 
                $request->user()->id, 
                $request->input('commit_message', 'Updated workflow')
            );
        }
        
        return new WorkflowResource($workflow->load(['organization', 'creator', 'executions']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workflow $workflow): JsonResponse
    {
        // Check if user has access to this workflow
        $this->authorizeWorkflowAccess($workflow);
        
        $workflow->delete();
        
        return response()->json(['message' => 'Workflow deleted successfully'], 200);
    }
    
    /**
     * Execute the specified workflow.
     */
    public function execute(Request $request, Workflow $workflow): JsonResponse
    {
        // Check if user has access and workflow is active
        $this->authorizeWorkflowAccess($workflow);
        
        if (!$workflow->isExecutable()) {
            return response()->json(['error' => 'Workflow is not active'], 400);
        }
        
        // Execute the workflow and return the execution details
        $execution = $this->executionService->executeWorkflow($workflow, $request->all());
        
        return response()->json([
            'message' => 'Workflow execution started',
            'execution_id' => $execution->execution_uuid,
            'status' => $execution->status,
        ], 200);
    }
    
    /**
     * Get all versions of a workflow.
     */
    public function versions(Workflow $workflow): AnonymousResourceCollection
    {
        $this->authorizeWorkflowAccess($workflow);
        
        $versions = $workflow->versions()->orderByDesc('version_number')->paginate(15);
        
        return WorkflowVersionResource::collection($versions);
    }
    
    /**
     * Get a specific version of a workflow.
     */
    public function version(Workflow $workflow, WorkflowVersion $version): WorkflowVersionResource
    {
        $this->authorizeWorkflowAccess($workflow);
        
        // Ensure the version belongs to this workflow
        if ($version->workflow_id !== $workflow->uuid) {
            abort(404, 'Version not found');
        }
        
        return new WorkflowVersionResource($version);
    }
    
    /**
     * Revert workflow to a specific version.
     */
    public function revertToVersion(Request $request, Workflow $workflow, WorkflowVersion $version): WorkflowResource
    {
        $this->authorizeWorkflowAccess($workflow);
        
        if ($version->workflow_id !== $workflow->uuid) {
            abort(404, 'Version not found');
        }
        
        // Update the workflow with the version data
        $workflow->update([
            'nodes' => $version->nodes,
            'connections' => $version->connections,
            'settings' => $version->settings,
            'name' => $version->name,
            'description' => $version->description,
            'version' => $version->version_number,
        ]);
        
        // Create a new version record for the revert
        $workflow->createVersion([
            'name' => $workflow->name,
            'description' => $workflow->description,
            'nodes' => $workflow->nodes,
            'connections' => $workflow->connections,
            'settings' => $workflow->settings,
        ], $request->user()->id, "Reverted to version {$version->version_number}");
        
        return new WorkflowResource($workflow);
    }
    
    /**
     * Authorize workflow access based on user permissions.
     */
    private function authorizeWorkflowAccess(Workflow $workflow): void
    {
        $user = Auth::user();
        
        // Check if the user owns the workflow or has access through organization
        $hasAccess = (
            $workflow->created_by === $user->id ||  // User created the workflow
            ($workflow->organization_id && $user->organizations->contains($workflow->organization_id)) // User in organization
        );
        
        if (!$hasAccess) {
            abort(403, 'Unauthorized to access this workflow');
        }
    }
}