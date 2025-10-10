<?php

namespace App\Domains\Workflow\Actions;

use App\Domains\Workflow\DTOs\WorkflowDTO;
use App\Domains\Workflow\Models\Workflow;
use Illuminate\Support\Facades\DB;

class CreateWorkflowAction
{
    public function execute(WorkflowDTO $dto): Workflow
    {
        return DB::transaction(function () use ($dto) {
            $workflow = Workflow::create([
                'uuid' => $dto->uuid,
                'name' => $dto->name,
                'description' => $dto->description,
                'status' => $dto->status,
                'definition' => $dto->definition,
                'nodes' => $dto->nodes,
                'connections' => $dto->connections,
                'settings' => $dto->settings,
                'version' => $dto->version,
                'created_by' => $dto->created_by,
                'updated_by' => $dto->updated_by,
                'organization_id' => $dto->organization_id,
            ]);

            // Create initial version
            $workflow->createVersion(
                $dto->toArray(),
                $dto->created_by,
                'Initial version'
            );

            return $workflow;
        });
    }
}
