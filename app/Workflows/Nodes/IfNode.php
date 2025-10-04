<?php

namespace App\Workflows\Nodes;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class IfNode extends BaseNode
{
    public function execute(array $input): array
    {
        $condition = $this->parameters['condition'] ?? 'true';

        $expressionLanguage = new ExpressionLanguage();
        $result = $expressionLanguage->evaluate($condition, ['input' => $input]);

        return [
            'status' => 'success',
            'data' => $input,
            'condition_result' => (bool) $result,
            'node_id' => $this->getId(),
            'node_type' => $this->getType(),
        ];
    }

    public function getType(): string
    {
        return 'if';
    }
}
