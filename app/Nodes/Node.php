<?php

namespace App\Nodes;

abstract class Node
{
    /**
     * The name of the node.
     */
    public string $name;

    /**
     * The description of the node.
     */
    public string $description;

    /**
     * Execute the node's logic.
     */
    abstract public function execute(array $input): array;
}
