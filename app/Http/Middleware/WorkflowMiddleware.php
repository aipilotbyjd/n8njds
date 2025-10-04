<?php

namespace App\Http\Middleware;

use App\Models\Workflow;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WorkflowMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $workflowId = $request->route('workflow');
        
        if (!$workflowId) {
            return $next($request);
        }

        // If the parameter is a Workflow model instance, get the ID
        if ($workflowId instanceof Workflow) {
            $workflowId = $workflowId->id;
        }
        
        $user = Auth::user();
        
        // Check if user has access to this workflow
        $workflow = Workflow::find($workflowId);
        
        if (!$workflow) {
            return response()->json([
                'message' => 'Workflow not found'
            ], 404);
        }
        
        $hasAccess = $workflow->user_id === $user->id || 
                     ($workflow->organization_id && $user->organizations()->where('organization_id', $workflow->organization_id)->exists());
        
        if (!$hasAccess) {
            return response()->json([
                'message' => 'Unauthorized to access this workflow'
            ], 403);
        }

        // Add the workflow to the request for later use
        $request->attributes->set('workflow', $workflow);

        return $next($request);
    }
}
