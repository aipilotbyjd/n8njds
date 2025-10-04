<?php

namespace App\Domains\Workflow\DataTransferObjects;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class WorkflowDTO extends Data
{
    public function __construct(
        public readonly ?string $uuid,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $status,
        public readonly ?array $definition,
        public readonly ?array $nodes,
        public readonly ?array $connections,
        public readonly ?array $settings,
        public readonly int $version,
        public readonly string $created_by,
        public readonly ?string $updated_by,
        public readonly ?int $organization_id,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            uuid: $request->input('uuid'),
            name: $request->input('name'),
            description: $request->input('description'),
            status: $request->input('status', 'draft'),
            definition: $request->input('definition'),
            nodes: $request->input('nodes'),
            connections: $request->input('connections'),
            settings: $request->input('settings'),
            version: $request->input('version', 1),
            created_by: $request->user()->id,
            updated_by: $request->user()->id,
            organization_id: $request->user()->currentOrganization?->id,
        );
    }
}