<?php

namespace App\Domains\Workflow\Http\Controllers\V1;

use App\Domains\Workflow\Services\WorkflowExecutionService;
use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    public function __construct(
        private WorkflowExecutionService $executionService
    ) {}

    /**
     * Handle incoming webhook requests
     */
    public function handleWebhook(Request $request, string $endpoint): JsonResponse
    {
        // Find the webhook by endpoint
        $webhook = Webhook::where('endpoint', $endpoint)->first();

        if (! $webhook || ! $webhook->is_active) {
            return response()->json(['error' => 'Webhook not found or inactive'], 404);
        }

        // Verify webhook signature if secret is set
        if ($webhook->secret) {
            $signature = $request->header('X-Signature') ?? $request->header('X-Hub-Signature');
            $payload = $request->getContent();

            if (! $signature || ! $this->verifySignature($payload, $signature, $webhook->secret)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // Update webhook stats
        $webhook->increment('trigger_count');
        $webhook->last_triggered_at = now();
        $webhook->save();

        try {
            // Execute the associated workflow
            $workflow = $webhook->workflow;

            if (! $workflow || ! $workflow->isExecutable()) {
                $webhook->increment('failed_count');

                return response()->json(['error' => 'Workflow not executable'], 400);
            }

            // Execute the workflow with the webhook payload
            $this->executionService->executeWorkflow($workflow, $request->all());

            return response()->json(['message' => 'Webhook processed successfully'], 200);
        } catch (\Exception $e) {
            $webhook->increment('failed_count');

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature(string $payload, string $signature, string $secret): bool
    {
        // This is a simplified signature verification
        // In a real implementation, you'd support multiple signature formats (GitHub, Stripe, etc.)
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create a new webhook
     */
    public function createWebhook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workflow_id' => 'required|uuid|exists:workflows,uuid',
            'endpoint' => 'required|string|unique:webhooks,endpoint',
            'method' => 'sometimes|string|in:GET,POST,PUT,PATCH,DELETE',
            'is_active' => 'sometimes|boolean',
        ]);

        $validator->validate(); // This throws ValidationException on failure

        $webhook = Webhook::create([
            'workflow_id' => $request->workflow_id,
            'endpoint' => $request->endpoint,
            'method' => $request->method ?? 'POST',
            'secret' => $request->secret ?? \Illuminate\Support\Str::random(32),
            'is_active' => $request->is_active ?? true,
            'settings' => $request->settings ?? [],
        ]);

        return response()->json(['webhook' => $webhook], 201);
    }

    /**
     * Get webhook statistics
     */
    public function getWebhookStats(string $endpoint): JsonResponse
    {
        $webhook = Webhook::where('endpoint', $endpoint)->first();

        if (! $webhook) {
            return response()->json(['error' => 'Webhook not found'], 404);
        }

        return response()->json([
            'webhook' => $webhook,
            'stats' => [
                'trigger_count' => $webhook->trigger_count,
                'failed_count' => $webhook->failed_count,
                'success_rate' => $webhook->trigger_count > 0
                    ? (($webhook->trigger_count - $webhook->failed_count) / $webhook->trigger_count) * 100
                    : 100,
                'last_triggered_at' => $webhook->last_triggered_at,
            ],
        ]);
    }
}
