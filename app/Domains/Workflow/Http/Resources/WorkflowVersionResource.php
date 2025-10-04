<?php

namespace App\Domains\Workflow\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowVersionResource extends JsonResource
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
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'version_number' => $this->version_number,
            'name' => $this->name,
            'description' => $this->description,
            'nodes' => $this->nodes,
            'connections' => $this->connections,
            'settings' => $this->settings,
            'created_by' => $this->created_by,
            'committed_at' => $this->committed_at,
            'commit_message' => $this->commit_message,
            'created_at' => $this->created_at,
        ];
    }
}