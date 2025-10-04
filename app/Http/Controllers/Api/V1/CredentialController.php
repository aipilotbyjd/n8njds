<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Services\CredentialService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CredentialController extends Controller
{
    public function __construct(
        private CredentialService $credentialService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $credentials = $this->credentialService->getOwnedByUser($request->user());
        
        return response()->json([
            'credentials' => $credentials
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'credential_data' => 'required|array',
            'credential_data.*' => 'string',
            'nodes_access' => 'nullable|array',
            'shared_with' => 'nullable|array',
            'expires_at' => 'nullable|date',
            'rotation_policy' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credential = $this->credentialService->create($request->all(), $request->user());

        return response()->json([
            'message' => 'Credential created successfully',
            'credential' => $credential
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $credential = $this->credentialService->getById($id, $request->user());
        
        if (!$credential) {
            return response()->json([
                'message' => 'Credential not found'
            ], 404);
        }

        if (!$this->credentialService->canAccess($request->user(), $credential)) {
            return response()->json([
                'message' => 'Unauthorized to access this credential'
            ], 403);
        }

        return response()->json([
            'credential' => $credential
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $credential = $this->credentialService->getById($id, $request->user());
        
        if (!$credential) {
            return response()->json([
                'message' => 'Credential not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:100',
            'credential_data' => 'sometimes|required|array',
            'credential_data.*' => 'string',
            'nodes_access' => 'nullable|array',
            'shared_with' => 'nullable|array',
            'expires_at' => 'nullable|date',
            'rotation_policy' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updatedCredential = $this->credentialService->update($credential, $request->all());

        return response()->json([
            'message' => 'Credential updated successfully',
            'credential' => $updatedCredential
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $credential = $this->credentialService->getById($id, $request->user());
        
        if (!$credential) {
            return response()->json([
                'message' => 'Credential not found'
            ], 404);
        }

        $this->credentialService->delete($credential);

        return response()->json([
            'message' => 'Credential deleted successfully'
        ]);
    }

    public function rotate(Request $request, string $id): JsonResponse
    {
        $credential = $this->credentialService->getById($id, $request->user());
        
        if (!$credential) {
            return response()->json([
                'message' => 'Credential not found'
            ], 404);
        }

        if (!$this->credentialService->canAccess($request->user(), $credential)) {
            return response()->json([
                'message' => 'Unauthorized to access this credential'
            ], 403);
        }

        $rotatedCredential = $this->credentialService->rotate($credential);

        return response()->json([
            'message' => 'Credential rotated successfully',
            'credential' => $rotatedCredential
        ]);
    }
}