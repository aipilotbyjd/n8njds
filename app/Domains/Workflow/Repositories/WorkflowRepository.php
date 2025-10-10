<?php

namespace App\Domains\Workflow\Repositories;

use App\Domains\Workflow\DataTransferObjects\WorkflowDTO;
use App\Domains\Workflow\Models\Workflow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    public function findById(string $id): ?Workflow
    {
        return Workflow::find($id);
    }

    public function findByUuid(string $uuid): ?Workflow
    {
        return Workflow::where('uuid', $uuid)->first();
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Workflow::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('name', 'LIKE', '%'.$filters['search'].'%')
                    ->orWhere('description', 'LIKE', '%'.$filters['search'].'%');
            });
        }

        return $query->with(['creator', 'organization', 'executions'])
            ->paginate($perPage);
    }

    public function create(WorkflowDTO $dto): Workflow
    {
        return Workflow::create($dto->toArray());
    }

    public function update(Workflow $workflow, WorkflowDTO $dto): Workflow
    {
        $workflow->update($dto->toArray());

        return $workflow;
    }

    public function delete(Workflow $workflow): bool
    {
        return $workflow->delete();
    }

    public function getByOrganization(int $organizationId, array $filters = []): Collection
    {
        $query = Workflow::where('organization_id', $organizationId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public function search(string $query, array $filters = []): Collection
    {
        $workflowQuery = Workflow::where('name', 'LIKE', '%'.$query.'%')
            ->orWhere('description', 'LIKE', '%'.$query.'%');

        if (! empty($filters['status'])) {
            $workflowQuery->where('status', $filters['status']);
        }

        return $workflowQuery->get();
    }
}
