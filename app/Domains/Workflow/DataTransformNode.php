<?php

namespace App\Workflows\Nodes;

class DataTransformNode extends BaseNode
{
    public function execute(array $input): array
    {
        $mapping = $this->parameters['mapping'] ?? [];
        $output = [];

        foreach ($mapping as $outputField => $inputField) {
            if (isset($input[$inputField])) {
                $output[$outputField] = $input[$inputField];
            }
        }

        return [
            'status' => 'success',
            'data' => $output,
            'node_id' => $this->getId(),
            'node_type' => $this->getType(),
        ];
    }

    public function getType(): string
    {
        return 'data-transform';
    }
}
