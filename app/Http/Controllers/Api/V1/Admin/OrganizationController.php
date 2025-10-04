<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the organizations.
     */
    public function index(Request $request): JsonResponse
    {
        $organizations = Organization::with(['owner', 'users'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['organizations' => $organizations]);
    }

    /**
     * Display the specified organization.
     */
    public function show(Organization $organization, Request $request): JsonResponse
    {
        return response()->json(['organization' => $organization->load(['owner', 'users'])]);
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'timezone' => 'nullable|string|max:50',
            'owner_id' => 'sometimes|required|exists:users,id',
        ]);

        $organization->update($request->only(['name', 'description', 'timezone']));

        // Only update owner if specified
        if ($request->has('owner_id')) {
            $organization->update(['owner_id' => $request->owner_id]);
        }

        return response()->json([
            'message' => 'Organization updated successfully',
            'organization' => $organization->load(['owner', 'users'])
        ]);
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(Organization $organization, Request $request): JsonResponse
    {
        $organization->delete();

        return response()->json([
            'message' => 'Organization deleted successfully'
        ]);
    }
}