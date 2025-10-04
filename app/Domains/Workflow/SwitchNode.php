<?php

namespace App\Workflows\Nodes;

class SwitchNode extends BaseNode
{
    public function execute(array $input): array
    {
        $field = $this->parameters['field'] ?? null;
        $cases = $this->parameters['cases'] ?? [];

        $value = $input[$field] ?? null;

        $outputBranch = 'default';
        foreach ($cases as $case) {
            if ($case['value'] == $value) {
                $outputBranch = $case['branch'];
                break;
            }
        }

        return [
            'status' => 'success',
            'data' => $input,
            'output_branch' => $outputBranch,
            'node_id' => $this->getId(),
            'node_type' => $this->getType(),
        ];
    }

    public function getType(): string
    {
        return 'switch';
    }
}
