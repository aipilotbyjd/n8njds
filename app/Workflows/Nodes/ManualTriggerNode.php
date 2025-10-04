<?php

namespace App\Workflows\Nodes;

class ManualTriggerNode extends BaseNode
{
    public function execute(array $input): array
    {
        // Manual trigger node just passes the input through
        return [
            'status' => 'success',
            'data' => $input,
            'node_id' => $this->getId(),
            'node_type' => $this->getType(),
        ];
    }

    public function validate(array $config): bool
    {
        // Manual trigger has no special validation
        return true;
    }

    public function getType(): string
    {
        return 'manual-trigger';
    }
}