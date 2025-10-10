<?php

namespace App\Services;

use App\Models\Event;
use App\Shared\Interfaces\EventStoreInterface;
use Illuminate\Support\Str;

class EventStoreService implements EventStoreInterface
{
    public function append(string $aggregateType, string $aggregateId, string $eventType, array $payload, int $version): void
    {
        Event::create([
            'id' => (string) Str::uuid(),
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'payload' => $payload,
            'version' => $version,
        ]);
    }

    public function load(string $aggregateType, string $aggregateId): array
    {
        return Event::where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->orderBy('version')
            ->get()
            ->toArray();
    }

    public function loadFromVersion(string $aggregateType, string $aggregateId, int $version): array
    {
        return Event::where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->where('version', '>', $version)
            ->orderBy('version')
            ->get()
            ->toArray();
    }
}
