<?php

namespace App\Domains\Auth\Services;

use App\Models\Credential;
use App\Models\User;
use App\Shared\Interfaces\ServiceInterface;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CredentialService implements ServiceInterface
{
    public function create(array $data, User $user): Credential
    {
        $credentialData = [
            'name' => $data['name'],
            'type' => $data['type'],
            'data' => Crypt::encryptString(json_encode($data['credential_data'])),
            'nodes_access' => $data['nodes_access'] ?? null,
            'owned_by' => $user->id,
            'shared_with' => $data['shared_with'] ?? null,
            'rotation_policy' => $data['rotation_policy'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'uuid' => (string) Str::uuid(),
        ];

        return Credential::create($credentialData);
    }

    public function update(Credential $credential, array $data): Credential
    {
        $updateData = [
            'name' => $data['name'] ?? $credential->name,
            'type' => $data['type'] ?? $credential->type,
            'nodes_access' => $data['nodes_access'] ?? $credential->nodes_access,
            'shared_with' => $data['shared_with'] ?? $credential->shared_with,
            'rotation_policy' => $data['rotation_policy'] ?? $credential->rotation_policy,
            'expires_at' => $data['expires_at'] ?? $credential->expires_at,
        ];

        // Only update credential data if provided
        if (isset($data['credential_data'])) {
            $updateData['data'] = Crypt::encryptString(json_encode($data['credential_data']));
        }

        $credential->update($updateData);

        return $credential;
    }

    public function delete(Credential $credential): bool
    {
        return $credential->delete();
    }

    public function getOwnedByUser(User $user): array
    {
        return Credential::ownedBy($user->id)->get()->toArray();
    }

    public function getById(string $id, User $user): ?Credential
    {
        return Credential::where('id', $id)
            ->where('owned_by', $user->id)
            ->first();
    }

    public function getByUuid(string $uuid, User $user): ?Credential
    {
        return Credential::where('uuid', $uuid)
            ->where(function ($query) use ($user) {
                $query->where('owned_by', $user->id)
                    ->orWhereJsonContains('shared_with', $user->id);
            })
            ->first();
    }

    public function canAccess(User $user, Credential $credential): bool
    {
        // Check if user owns the credential or has it shared with them
        return $credential->owned_by === $user->id ||
               (is_array($credential->shared_with) && in_array($user->id, $credential->shared_with));
    }

    public function rotate(Credential $credential): Credential
    {
        // This is a simplified rotation process
        // In a real application, this would involve more complex logic
        $credential->update([
            'last_rotated_at' => now(),
            'next_rotation_at' => now()->addDays(90), // Default: rotate every 90 days
        ]);

        return $credential;
    }
}
