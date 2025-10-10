<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizationMemberController extends Controller
{
    /**
     * Display a listing of the organization members.
     */
    public function index(Organization $organization, Request $request): JsonResponse
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

        $members = $organization->users()->withPivot('role', 'is_active', 'joined_at')->get();

        return response()->json([
            'members' => $members,
        ]);
    }

    /**
     * Add a member to the organization.
     */
    public function store(Request $request, Organization $organization): JsonResponse
    {
        $user = $request->user();

        // Check if user is owner or admin of the organization
        $userMembership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->first();

        if (! $userMembership || ! in_array($userMembership->role, ['owner', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized to add members to this organization',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'role' => 'required|in:member,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $member = User::where('email', $request->email)->first();

        // Check if user is already a member
        $existingMembership = OrganizationUser::where([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
        ])->first();

        if ($existingMembership) {
            return response()->json([
                'message' => 'User is already a member of this organization',
            ], 422);
        }

        $membership = OrganizationUser::create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => $request->role,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Member added successfully',
            'member' => $member,
            'membership' => $membership,
        ], 201);
    }

    /**
     * Update a member's role in the organization.
     */
    public function update(Request $request, Organization $organization, User $member): JsonResponse
    {
        $user = $request->user();

        // Check if user is owner or admin of the organization
        $userMembership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->first();

        if (! $userMembership || ! in_array($userMembership->role, ['owner', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized to update members in this organization',
            ], 403);
        }

        // Check if the member exists in the organization
        $membership = OrganizationUser::where([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
        ])->first();

        if (! $membership) {
            return response()->json([
                'message' => 'User is not a member of this organization',
            ], 404);
        }

        // Prevent changing the owner's role
        if ($organization->owner_id === $member->id) {
            return response()->json([
                'message' => 'Cannot change the owner\'s role',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:member,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $membership->update([
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'Member role updated successfully',
            'membership' => $membership,
        ]);
    }

    /**
     * Remove a member from the organization.
     */
    public function destroy(Request $request, Organization $organization, User $member): JsonResponse
    {
        $user = $request->user();

        // Check if user is owner or admin of the organization
        $userMembership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->first();

        if (! $userMembership || ! in_array($userMembership->role, ['owner', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized to remove members from this organization',
            ], 403);
        }

        // Check if the member exists in the organization
        $membership = OrganizationUser::where([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
        ])->first();

        if (! $membership) {
            return response()->json([
                'message' => 'User is not a member of this organization',
            ], 404);
        }

        // Prevent removing the owner
        if ($organization->owner_id === $member->id) {
            return response()->json([
                'message' => 'Cannot remove the owner from the organization',
            ], 403);
        }

        // If removing self, ensure it's not the only admin
        if ($user->id === $member->id && $userMembership->role === 'admin') {
            $adminCount = $organization->users()
                ->wherePivot('role', 'admin')
                ->wherePivot('is_active', true)
                ->count();

            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'Cannot remove the last admin from the organization',
                ], 403);
            }
        }

        $membership->delete();

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }
}
