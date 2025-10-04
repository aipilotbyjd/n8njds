<?php

namespace App\Domains\User\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function index(Request $request)
    {
        return $request->user();
    }
    
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return $request->user();
    }
    
    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        // Validate and update the user's profile
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $request->user()->id,
        ]);
        
        $user = $request->user();
        $user->update($request->only(['name', 'email']));
        
        return response()->json($user);
    }
}
