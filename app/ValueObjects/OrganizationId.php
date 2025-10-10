<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class OrganizationId extends ValueObject
{
    public function __construct(public readonly string $value)
    {
        $this->attributes = ['value' => $value];
        parent::__construct($this->attributes);
    }

    protected function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('Organization ID cannot be empty');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
