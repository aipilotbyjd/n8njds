<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_type',
        'workflow_id',
        'execution_id',
        'node_id',
        'unit',
        'value',
        'tags',
        'measured_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'value' => 'decimal:4',
        'measured_at' => 'datetime',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id', 'uuid');
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(WorkflowExecution::class, 'execution_id');
    }
}