<?php

namespace App\Workflows\Nodes;

interface NodeInterface
{
    public function execute(array $input): array;
    public function validate(array $config): bool;
    public function getType(): string;
}