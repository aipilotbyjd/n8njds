<?php

namespace App\Workflows\Nodes;

interface NodeInterface
{
    public function execute(array $input): array;
    public function validate(array $config): bool;
    public function getType(): string;
    public function getId(): string;
    public function getName(): string;
    public function getDescription(): string;
    public function getParameters(): array;
    public function setParameters(array $parameters): void;
    public function getConfigSchema(): array;
}