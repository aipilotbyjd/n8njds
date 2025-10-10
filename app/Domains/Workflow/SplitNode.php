<?php

namespace App\Workflows\Nodes;

class SplitNode extends BaseNode
{
    public function execute(array $input): array
    {
        $field = $this->parameters['field'] ?? null;

        if (! $field || ! isset($input[$field]) || ! is_array($input[$field])) {
            return [
                'status' => 'error',
                'message' => 'Field to split is not an array or does not exist.',
                'node_id' => $this->getId(),
                'node_type' => $this->getType(),
            ];
        }

        $items = $input[$field];

        return [
            'status' => 'success',
            'data' => $items,
            'is_split' => true,
            'node_id' => $this->getId(),
            'node_type' => $this->getType(),
        ];
    }

    public function getType(): string
    {
        return 'split';
    }
}
