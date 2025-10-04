<?php

namespace App\ValueObjects;

abstract class ValueObject
{
    protected function __construct(
        protected array $attributes
    ) {
        $this->validate();
    }

    abstract protected function validate(): void;

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(string $property)
    {
        return $this->attributes[$property] ?? null;
    }

    public function __isset(string $property): bool
    {
        return isset($this->attributes[$property]);
    }
}