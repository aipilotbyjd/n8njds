<?php

namespace App\Services;

use App\Models\Trigger;
use App\Models\Workflow;
use App\Shared\Interfaces\ServiceInterface;

class TriggerService implements ServiceInterface
{
    public function handleTrigger(Trigger $trigger, array $payload = []): bool
    {
        if (! $trigger->is_active) {
            return false;
        }

        try {
            // Update trigger stats
            $trigger->increment('execution_count');
            $trigger->last_executed_at = now();
            $trigger->save();

            // Execute the associated workflow
            $workflow = $trigger->workflow;

            if ($workflow && $workflow->isExecutable()) {
                $executionService = new WorkflowExecutionService;
                $executionService->executeWorkflow($workflow, $payload);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            // Log the error but don't fail the trigger handling
            \Log::error('Trigger execution failed', [
                'trigger_id' => $trigger->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function createTrigger(array $data): Trigger
    {
        return Trigger::create([
            'workflow_id' => $data['workflow_id'],
            'type' => $data['type'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'configuration' => $data['configuration'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'settings' => $data['settings'] ?? [],
        ]);
    }

    public function updateTrigger(Trigger $trigger, array $data): Trigger
    {
        $trigger->update([
            'name' => $data['name'] ?? $trigger->name,
            'description' => $data['description'] ?? $trigger->description,
            'configuration' => $data['configuration'] ?? $trigger->configuration,
            'is_active' => $data['is_active'] ?? $trigger->is_active,
            'settings' => $data['settings'] ?? $trigger->settings,
        ]);

        return $trigger;
    }

    public function scheduleTrigger(Trigger $trigger): void
    {
        if ($trigger->type === 'schedule' && $trigger->is_active) {
            $configuration = $trigger->configuration;
            $scheduleExpression = $configuration['cron'] ?? null;

            if ($scheduleExpression) {
                // In a real implementation, you'd use Laravel's task scheduling
                // or a job queue to handle scheduled triggers
                \Log::info("Scheduled trigger created for workflow {$trigger->workflow_id}", [
                    'cron' => $scheduleExpression,
                    'trigger_id' => $trigger->id,
                ]);
            }
        }
    }

    public function handleScheduledTriggers(): void
    {
        // Find all active scheduled triggers
        $scheduledTriggers = Trigger::where('type', 'schedule')
            ->where('is_active', true)
            ->get();

        foreach ($scheduledTriggers as $trigger) {
            $configuration = $trigger->configuration;
            $scheduleExpression = $configuration['cron'] ?? null;

            // Check if the trigger should run now based on the cron expression
            if ($this->shouldRunNow($scheduleExpression)) {
                $this->handleTrigger($trigger);
            }
        }
    }

    private function shouldRunNow(?string $cronExpression): bool
    {
        if (! $cronExpression) {
            return false;
        }

        // This is a simplified version - in a real implementation, you'd use a proper cron parser
        // For now, just return true to simulate scheduled execution
        return true;
    }

    public function handleEventTrigger(string $eventType, array $eventData): void
    {
        // Find all event-based triggers for this event type
        $eventTriggers = Trigger::where('type', 'event')
            ->where('is_active', true)
            ->whereJsonContains('configuration->event_types', $eventType)
            ->get();

        foreach ($eventTriggers as $trigger) {
            $this->handleTrigger($trigger, $eventData);
        }
    }

    public function handleWebhookTrigger(Trigger $trigger, array $webhookData): void
    {
        // Webhook triggers are handled directly through the webhook endpoint
        // This method is for internal webhook trigger handling
        $this->handleTrigger($trigger, $webhookData);
    }
}
