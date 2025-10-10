<?php

namespace App\Domains\Workflow\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'nodes' => $this->nodes,
            'connections' => $this->connections,
            'settings' => $this->settings,
            'version' => $this->version,
            'organization_id' => $this->organization_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'last_executed_at' => $this->last_executed_at,
            'execution_count' => $this->execution_count,
            'tag_ids' => $this->tag_ids,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'execution_stats' => $this->getExecutionStats(),
            'current_version' => $this->whenLoaded('currentVersion'),
            'versions' => WorkflowVersionResource::collection($this->whenLoaded('versions')),
        ];
    }
}
