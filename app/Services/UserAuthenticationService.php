<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use App\Shared\Interfaces\ServiceInterface;

class UserAuthenticationService implements ServiceInterface
{
    public function authenticate(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        // Revoke any existing tokens for this user
        $user->tokens()->delete();

        // Create a new token for the user
        $token = $user->createToken('auth-token')->accessToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function register(array $data): ?array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Create a new token for the user
        $token = $user->createToken('auth-token')->accessToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user): bool
    {
        // Revoke the current token
        $tokenId = request()->bearerToken();
        
        $token = $user->tokens()->where('id', $tokenId)->first();
        if ($token) {
            $token->revoke();
            return true;
        }

        return false;
    }
}