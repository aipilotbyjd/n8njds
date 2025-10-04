<?php

namespace App\Workflows\Nodes;

abstract class BaseNode implements NodeInterface
{
    protected string $id;
    protected string $name;
    protected array $parameters;

    public function __construct(string $id, string $name, array $parameters = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    abstract public function execute(array $input): array;

    public function validate(array $config): bool
    {
        // Default validation - can be overridden by child classes
        return true;
    }

    abstract public function getType(): string;
}