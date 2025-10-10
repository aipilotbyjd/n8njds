<?php

namespace App\Workflows\Nodes;

use Illuminate\Support\Facades\Log;

abstract class BaseNode implements NodeInterface
{
    protected string $id;

    protected string $name;

    protected string $description;

    protected array $parameters;

    public function __construct(string $id, string $name, array $parameters = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->parameters = $parameters;
        $this->description = '';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
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
        try {
            // Default validation - can be overridden by child classes
            return true;
        } catch (\Exception $e) {
            Log::error('Node validation failed', [
                'node_type' => static::class,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getConfigSchema(): array
    {
        // Default implementation - specific nodes should override this
        return [
            'type' => $this->getType(),
            'name' => $this->getName(),
            'parameters' => $this->parameters,
        ];
    }

    abstract public function getType(): string;
}
