<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowExecution extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workflow_executions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'execution_uuid',
        'workflow_id',
        'user_id',
        'status',
        'started_at',
        'finished_at',
        'mode',
        'data',
        'error',
        'execution_time',
        'node_executions',
        'statistics',
        'priority',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'error' => 'array',
        'node_executions' => 'array',
        'statistics' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $keyType = 'string';

    public $incrementing = false;

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
     * Get the workflow that owns the execution.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id', 'uuid');
    }

    /**
     * Get the user that owns the execution.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get executions by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get executions by workflow.
     */
    public function scopeByWorkflow($query, string $workflowUuid)
    {
        return $query->where('workflow_id', $workflowUuid);
    }

    /**
     * Check if the execution is complete.
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['success', 'error', 'canceled']);
    }

    /**
     * Get the execution duration in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return $this->finished_at->diffInSeconds($this->started_at);
    }
}