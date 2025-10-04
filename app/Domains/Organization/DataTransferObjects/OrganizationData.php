<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

class OrganizationData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public string $timezone = 'UTC',
        public ?array $settings = null,
        public ?string $userId = null, // Owner ID
    ) {
    }
}