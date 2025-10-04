<?php

namespace App\Domains\Workflow\Repositories;

use App\Domains\Workflow\Models\Workflow;
use App\Domains\Workflow\DataTransferObjects\WorkflowDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WorkflowRepositoryInterface
{
    public function findById(string $id): ?Workflow;
    
    public function findByUuid(string $uuid): ?Workflow;
    
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function create(WorkflowDTO $dto): Workflow;
    
    public function update(Workflow $workflow, WorkflowDTO $dto): Workflow;
    
    public function delete(Workflow $workflow): bool;
    
    public function getByOrganization(int $organizationId, array $filters = []): Collection;
    
    public function search(string $query, array $filters = []): Collection;
}