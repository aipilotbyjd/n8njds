<?php

namespace App\Workflows\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowExecution extends Model
{
    protected $fillable = [
        'workflow_id',
        'status',
        'started_at',
        'finished_at',
        'data',
        'error',
        'execution_time',
    ];

    protected $casts = [
        'data' => 'array',
        'error' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
