<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowExecution extends Model
{
    use HasFactory;

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

    protected $casts = [
        'data' => 'array',
        'error' => 'array',
        'node_executions' => 'array',
        'statistics' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}