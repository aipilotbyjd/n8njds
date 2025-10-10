<?php

namespace App\Workflows\Repositories;

use App\Models\Workflow;
use App\Repositories\BaseRepository;

class WorkflowRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Workflow;
    }

    public function getActiveWorkflows()
    {
        return $this->model->where('is_active', true)->get();
    }

    public function getWorkflowsByUser(string $userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function getWorkflowsByOrganization(string $organizationId)
    {
        return $this->model->where('organization_id', $organizationId)->get();
    }
}
