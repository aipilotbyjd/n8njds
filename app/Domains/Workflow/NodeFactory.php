<?php

namespace App\Workflows\Nodes;

class NodeFactory
{
    private static array $nodeTypes = [
        'manual-trigger' => ManualTriggerNode::class,
        'http-request' => HttpRequestNode::class,
        'log' => LogNode::class,
        'if' => IfNode::class,
        'switch' => SwitchNode::class,
        'data-transform' => DataTransformNode::class,
        'merge' => MergeNode::class,
        'split' => SplitNode::class,
    ];

    public static function create(string $type, string $id, string $name, array $parameters = []): ?NodeInterface
    {
        if (!isset(self::$nodeTypes[$type])) {
            return null;
        }

        $class = self::$nodeTypes[$type];
        return new $class($id, $name, $parameters);
    }

    public static function registerNodeType(string $type, string $class): void
    {
        self::$nodeTypes[$type] = $class;
    }

    public static function getAvailableNodeTypes(): array
    {
        return array_keys(self::$nodeTypes);
    }
}