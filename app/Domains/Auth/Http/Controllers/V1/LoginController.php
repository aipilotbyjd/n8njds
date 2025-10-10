<?php

namespace App\Domains\Auth\Http\Controllers\V1;

use App\Domains\Auth\Services\UserAuthenticationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(
        private UserAuthenticationService $authService
    ) {}

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

        if (! $result) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
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
                'message' => 'Logout successful',
            ]);
        }

        return response()->json([
            'message' => 'Logout failed',
        ], 400);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Refresh the user's token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token
        $tokenId = $request->bearerToken();
        $token = $user->tokens()->where('id', $tokenId)->first();
        if ($token) {
            $token->revoke();
        }

        // Create a new token for the user
        $newToken = $user->createToken('auth-token')->accessToken;

        return response()->json([
            'message' => 'Token refreshed successfully',
            'user' => $user,
            'token' => $newToken,
            'token_type' => 'Bearer',
        ]);
    }
}
