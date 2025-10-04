<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private UserAuthenticationService $authService
    ) {
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $result = $this->authService->authenticate($request->email, $request->password);

        if (!$result) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.'
            ], 422);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $result['user'],
            'token' => $result['token'],
            'token_type' => $result['token_type'],
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): JsonResponse
    {
        $success = $this->authService->logout($request->user());

        if ($success) {
            return response()->json([
                'message' => 'Logout successful'
            ]);
        }

        return response()->json([
            'message' => 'Logout failed'
        ], 400);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}