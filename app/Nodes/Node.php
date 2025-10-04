<?php

namespace App\Nodes;

abstract class Node
{
    /**
     * The name of the node.
     *
     * @var string
     */
    public string $name;

    /**
     * The description of the node.
     *
     * @var string
     */
    public string $description;

    /**
     * Execute the node's logic.
     *
     * @param array $input
     * @return array
     */
    abstract public function execute(array $input): array;
}
