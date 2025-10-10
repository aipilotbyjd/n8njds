<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'type',
        'data',
        'nodes_access',
        'owned_by',
        'shared_with',
        'encryption_key_id',
        'rotation_policy',
        'last_rotated_at',
        'next_rotation_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'nodes_access' => 'array',
        'shared_with' => 'array',
        'rotation_policy' => 'array',
        'last_rotated_at' => 'datetime',
        'next_rotation_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'data',
    ];

    protected $appends = [
        'decrypted_data',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owned_by');
    }

    public function getDecryptedDataAttribute(): ?array
    {
        if ($this->data) {
            try {
                return json_decode(Crypt::decryptString($this->data), true);
            } catch (\Exception $e) {
                // Log the error but don't expose it
                \Log::error('Failed to decrypt credential data', [
                    'credential_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return null;
    }

    public function setEncryptedDataAttribute(array $value): void
    {
        $this->data = Crypt::encryptString(json_encode($value));
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOwnedBy($query, string $userId)
    {
        return $query->where('owned_by', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('expires_at', '<=', now()->addDays($days));
    }
}
