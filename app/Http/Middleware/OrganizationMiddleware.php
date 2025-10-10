<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrganizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->route('organization');

        if (! $organizationId) {
            return $next($request);
        }

        // If the parameter is an Organization model instance, get the ID
        if ($organizationId instanceof Organization) {
            $organizationId = $organizationId->id;
        }

        $user = Auth::user();

        // Check if user has access to this organization
        $hasAccess = $user->organizations()->where('organization_id', $organizationId)->exists() ||
                     $user->ownedOrganizations()->where('id', $organizationId)->exists();

        if (! $hasAccess) {
            return response()->json([
                'message' => 'Unauthorized to access this organization',
            ], 403);
        }

        return $next($request);
    }
}
