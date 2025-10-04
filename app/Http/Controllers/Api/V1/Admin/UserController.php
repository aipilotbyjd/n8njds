<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::with(['ownedOrganizations', 'organizations'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['users' => $users]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user, Request $request): JsonResponse
    {
        return response()->json(['user' => $user->load(['ownedOrganizations', 'organizations'])]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'current_organization_id' => 'nullable|exists:organizations,id',
        ]);

        $user->update($request->only(['name', 'email', 'current_organization_id']));

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load(['ownedOrganizations', 'organizations'])
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user, Request $request): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Cannot delete yourself'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}