<?php

namespace App\Shared\Interfaces;

use App\Models\Event;

interface EventStoreInterface
{
    public function append(string $aggregateType, string $aggregateId, string $eventType, array $payload, int $version): void;
    public function load(string $aggregateType, string $aggregateId): array;
    public function loadFromVersion(string $aggregateType, string $aggregateId, int $version): array;
}