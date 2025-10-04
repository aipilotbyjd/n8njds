<?php

namespace App\Workflows\Nodes;

class MergeNode extends BaseNode
{
    public function execute(array $input): array
    {
        $mergedData = array_merge($this->parameters['static_data'] ?? [], $input);

        return [
            'status' => 'success',
            'data' => $mergedData,
            'node_id' => $this->getId(),
            'node_type' => $this->getType(),
        ];
    }

    public function getType(): string
    {
        return 'merge';
    }
}
