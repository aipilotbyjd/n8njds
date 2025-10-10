<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;

class WorkflowPolicy
{
    /**
     * Determine whether the user can view any workflows.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('workflows.read');
    }

    /**
     * Determine whether the user can view the workflow.
     */
    public function view(User $user, Workflow $workflow): bool
    {
        return $user->can('workflows.read') && $user->id === $workflow->user_id;
    }

    /**
     * Determine whether the user can create workflows.
     */
    public function create(User $user): bool
    {
        return $user->can('workflows.create');
    }

    /**
     * Determine whether the user can update the workflow.
     */
    public function update(User $user, Workflow $workflow): bool
    {
        return $user->can('workflows.update') && $user->id === $workflow->user_id;
    }

    /**
     * Determine whether the user can delete the workflow.
     */
    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->can('workflows.delete') && $user->id === $workflow->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Workflow $workflow): bool
    {
        return $user->can('workflows.update') && $user->id === $workflow->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Workflow $workflow): bool
    {
        return $user->can('workflows.delete') && $user->id === $workflow->user_id;
    }
}
