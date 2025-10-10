<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowVersion extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workflow_id',
        'version_number',
        'name',
        'description',
        'nodes',
        'connections',
        'settings',
        'created_by',
        'committed_at',
        'commit_message',
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
        'committed_at' => 'datetime',
    ];

    /**
     * Get the workflow that owns the version.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id', 'uuid');
    }

    /**
     * Get the user who created the version.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
