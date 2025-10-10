<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'status',
        'definition',
        'nodes',
        'connections',
        'settings',
        'version',
        'created_by',
        'updated_by',
        'last_executed_at',
        'execution_count',
        'tag_ids',
        'search_vector',
        'organization_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nodes' => 'array',
        'connections' => 'array',
        'settings' => 'array',
        'tag_ids' => 'array',
        'definition' => 'array',
        'last_executed_at' => 'datetime',
        'search_vector' => 'array',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'search_vector',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'execution_stats',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the user that created the workflow.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that last updated the workflow.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the organization that owns the workflow.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the workflow executions.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class, 'workflow_id', 'uuid');
    }

    /**
     * Get the workflow versions.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class, 'workflow_id', 'uuid');
    }

    /**
     * Get the current version of the workflow.
     */
    public function currentVersion(): HasMany
    {
        return $this->versions()->latestOfMany('version_number');
    }

    /**
     * Scope to get active workflows.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get workflows by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get workflows by organization.
     */
    public function scopeByOrganization(Builder $query, int|string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope to search workflows.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Mark the workflow as executed.
     */
    public function markAsExecuted(): void
    {
        $this->update([
            'last_executed_at' => now(),
            'execution_count' => $this->execution_count + 1,
        ]);
    }

    /**
     * Check if the workflow is executable.
     */
    public function isExecutable(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the workflow's execution stats attribute.
     */
    public function getExecutionStatsAttribute(): array
    {
        return [
            'total_executions' => $this->execution_count,
            'success_rate' => $this->executions()->where('status', 'success')->count() / max($this->execution_count, 1) * 100,
            'avg_execution_time' => $this->executions()->avg('execution_time') ?? 0,
            'last_execution' => $this->executions()->latest()->first(),
        ];
    }

    /**
     * Create a new version of the workflow
     */
    public function createVersion(array $data, string $userId, ?string $commitMessage = null): WorkflowVersion
    {
        $versionNumber = ($this->versions()->max('version_number') ?? 0) + 1;

        return WorkflowVersion::create([
            'workflow_id' => $this->uuid,
            'version_number' => $versionNumber,
            'name' => $data['name'] ?? $this->name,
            'description' => $data['description'] ?? $this->description,
            'nodes' => $data['nodes'] ?? $this->nodes,
            'connections' => $data['connections'] ?? $this->connections,
            'settings' => $data['settings'] ?? $this->settings,
            'created_by' => $userId,
            'committed_at' => now(),
            'commit_message' => $commitMessage,
        ]);
    }
}
