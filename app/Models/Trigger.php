<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'workflow_id',
        'type', // 'webhook', 'schedule', 'event', 'manual'
        'name',
        'description',
        'configuration',
        'is_active',
        'last_executed_at',
        'execution_count',
        'settings',
    ];

    protected $casts = [
        'configuration' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
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
}
