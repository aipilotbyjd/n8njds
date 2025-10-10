<?php

namespace App\Domains\Organization\Http\Controllers\V1;

use App\Domains\Organization\DataTransferObjects\OrganizationData;
use App\Domains\Organization\Services\OrganizationApplicationService;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationApplicationService $organizationService
    ) {}

    /**
     * Display a listing of the organizations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $organizations = $user->organizations()->get();

        return response()->json([
            'organizations' => $organizations,
        ]);
    }

    /**
     * Store a newly created organization in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = new OrganizationData(
            name: $request->name,
            description: $request->description,
            timezone: $request->timezone ?? 'UTC',
            userId: $request->user()->id
        );

        $aggregate = $this->organizationService->createOrganization($data);

        // Set this as the user's current organization
        $request->user()->update([
            'current_organization_id' => $aggregate->getId()->value,
        ]);

        return response()->json([
            'message' => 'Organization created successfully',
            'organization' => $aggregate,
        ], 201);
    }

    /**
     * Display the specified organization.
     */
    public function show(Organization $organization, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this organization
        $membership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->first();

        if (! $membership) {
            return response()->json([
                'message' => 'Unauthorized to access this organization',
            ], 403);
        }

        return response()->json([
            'organization' => $organization->load('owner'),
        ]);
    }

    /**
     * Update the specified organization in storage.
     */
    public function update(Request $request, Organization $organization): JsonResponse
    {
        $user = $request->user();

        // Check if user is the owner of the organization
        if ($organization->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to update this organization',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = new OrganizationData(
            name: $request->name ?? $organization->name,
            description: $request->description ?? $organization->description,
            timezone: $request->timezone ?? $organization->timezone,
            userId: $user->id
        );

        $aggregate = $this->organizationService->getOrganization($organization->id);
        if ($aggregate) {
            $updatedAggregate = $this->organizationService->updateOrganization($aggregate, $data);

            return response()->json([
                'message' => 'Organization updated successfully',
                'organization' => $updatedAggregate,
            ]);
        }

        return response()->json([
            'message' => 'Organization not found',
        ], 404);
    }

    /**
     * Switch the user's current organization.
     */
    public function switch(Organization $organization, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user belongs to this organization
        $membership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->first();

        if (! $membership) {
            return response()->json([
                'message' => 'Unauthorized to access this organization',
            ], 403);
        }

        $user->update([
            'current_organization_id' => $organization->id,
        ]);

        return response()->json([
            'message' => 'Organization switched successfully',
            'organization' => $organization,
        ]);
    }

    /**
     * Remove the specified organization from storage.
     */
    public function destroy(Organization $organization, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is the owner of the organization
        if ($organization->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to delete this organization',
            ], 403);
        }

        $aggregate = $this->organizationService->getOrganization($organization->id);
        if ($aggregate) {
            $aggregate->delete();
        }

        return response()->json([
            'message' => 'Organization deleted successfully',
        ]);
    }
}
