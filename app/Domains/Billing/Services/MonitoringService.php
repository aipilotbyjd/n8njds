<?php

namespace App\Domains\Billing\Services;

use App\Models\PerformanceMetric;
use App\Models\SystemLog;
use App\Models\WorkflowMonitoring;
use App\Shared\Interfaces\ServiceInterface;
use Illuminate\Support\Facades\Log;

class MonitoringService implements ServiceInterface
{
    public function logMetric(string $metricName, string $metricType, float $value, array $labels = [], ?string $workflowId = null): void
    {
        WorkflowMonitoring::create([
            'workflow_id' => $workflowId,
            'metric_name' => $metricName,
            'metric_type' => $metricType,
            'value' => $value,
            'labels' => $labels,
            'measured_at' => now(),
        ]);
    }

    public function logPerformanceMetric(string $metricType, float $value, string $unit, array $tags = [], ?string $workflowId = null, ?string $executionId = null, ?string $nodeId = null): void
    {
        PerformanceMetric::create([
            'metric_type' => $metricType,
            'workflow_id' => $workflowId,
            'execution_id' => $executionId,
            'node_id' => $nodeId,
            'unit' => $unit,
            'value' => $value,
            'tags' => $tags,
            'measured_at' => now(),
        ]);
    }

    public function log(string $level, string $channel, string $message, array $context = []): void
    {
        SystemLog::create([
            'level' => $level,
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
            'logged_at' => now(),
        ]);

        // Also log to Laravel's default logger for consistency
        Log::channel($channel)->log($level, $message, $context);
    }

    public function logError(string $message, array $context = [], ?string $stackTrace = null, ?string $workflowId = null, ?string $executionId = null): void
    {
        SystemLog::create([
            'level' => 'error',
            'channel' => 'workflow',
            'message' => $message,
            'context' => $context,
            'stack_trace' => $stackTrace,
            'workflow_id' => $workflowId,
            'execution_id' => $executionId,
            'logged_at' => now(),
        ]);

        Log::error($message, $context);
    }

    public function logWarning(string $message, array $context = []): void
    {
        $this->log('warning', 'workflow', $message, $context);
    }

    public function logInfo(string $message, array $context = []): void
    {
        $this->log('info', 'workflow', $message, $context);
    }

    public function logDebug(string $message, array $context = []): void
    {
        $this->log('debug', 'workflow', $message, $context);
    }

    public function getWorkflowMetrics(string $workflowId, ?string $metricName = null, ?\DateTime $from = null, ?\DateTime $to = null): array
    {
        $query = WorkflowMonitoring::where('workflow_id', $workflowId);

        if ($metricName) {
            $query->where('metric_name', $metricName);
        }

        if ($from) {
            $query->where('measured_at', '>=', $from);
        }

        if ($to) {
            $query->where('measured_at', '<=', $to);
        }

        return $query->orderBy('measured_at', 'desc')->get()->toArray();
    }

    public function getPerformanceMetrics(string $workflowId, ?string $metricType = null, ?\DateTime $from = null, ?\DateTime $to = null): array
    {
        $query = PerformanceMetric::where('workflow_id', $workflowId);

        if ($metricType) {
            $query->where('metric_type', $metricType);
        }

        if ($from) {
            $query->where('measured_at', '>=', $from);
        }

        if ($to) {
            $query->where('measured_at', '<=', $to);
        }

        return $query->orderBy('measured_at', 'desc')->get()->toArray();
    }

    public function getSystemLogs(?string $level = null, ?string $channel = null, ?\DateTime $from = null, ?\DateTime $to = null): array
    {
        $query = SystemLog::query();

        if ($level) {
            $query->where('level', $level);
        }

        if ($channel) {
            $query->where('channel', $channel);
        }

        if ($from) {
            $query->where('logged_at', '>=', $from);
        }

        if ($to) {
            $query->where('logged_at', '<=', $to);
        }

        return $query->orderBy('logged_at', 'desc')->get()->toArray();
    }

    public function getExecutionPerformance(string $executionId): array
    {
        $metrics = PerformanceMetric::where('execution_id', $executionId)->get();

        $executionTime = $metrics->firstWhere('metric_type', 'execution_time');
        $memoryUsage = $metrics->firstWhere('metric_type', 'memory_usage');

        return [
            'execution_time' => $executionTime ? $executionTime->value : null,
            'memory_usage' => $memoryUsage ? $memoryUsage->value : null,
            'metrics' => $metrics->toArray(),
        ];
    }

    public function getWorkflowPerformance(string $workflowId): array
    {
        $recentExecutions = PerformanceMetric::where('workflow_id', $workflowId)
            ->where('metric_type', 'execution_time')
            ->orderBy('measured_at', 'desc')
            ->limit(10)
            ->get();

        $avgExecutionTime = $recentExecutions->avg('value');
        $minExecutionTime = $recentExecutions->min('value');
        $maxExecutionTime = $recentExecutions->max('value');

        return [
            'avg_execution_time' => $avgExecutionTime,
            'min_execution_time' => $minExecutionTime,
            'max_execution_time' => $maxExecutionTime,
            'execution_count' => $recentExecutions->count(),
        ];
    }

    public function getSystemHealth(): array
    {
        $recentErrors = SystemLog::where('level', 'error')
            ->where('logged_at', '>=', now()->subMinutes(5))
            ->count();

        $activeWorkflows = \App\Models\Workflow::where('status', 'active')
            ->count();

        $recentExecutions = \App\Models\WorkflowExecution::where('updated_at', '>=', now()->subMinutes(5))
            ->count();

        return [
            'status' => $recentErrors > 10 ? 'unhealthy' : ($recentErrors > 0 ? 'warning' : 'healthy'),
            'recent_errors' => $recentErrors,
            'active_workflows' => $activeWorkflows,
            'recent_executions' => $recentExecutions,
            'timestamp' => now(),
        ];
    }

    /**
     * Get aggregated metrics for dashboard
     */
    public function getDashboardMetrics(): array
    {
        $startOfToday = now()->startOfDay();
        $startOfWeek = now()->startOfWeek();
        $startOfMonth = now()->startOfMonth();

        return [
            'today' => [
                'executions' => \App\Models\WorkflowExecution::where('created_at', '>=', $startOfToday)->count(),
                'errors' => \App\Models\WorkflowExecution::where('status', 'error')->where('created_at', '>=', $startOfToday)->count(),
                'success_rate' => $this->getSuccessRateForPeriod($startOfToday),
            ],
            'week' => [
                'executions' => \App\Models\WorkflowExecution::where('created_at', '>=', $startOfWeek)->count(),
                'errors' => \App\Models\WorkflowExecution::where('status', 'error')->where('created_at', '>=', $startOfWeek)->count(),
                'success_rate' => $this->getSuccessRateForPeriod($startOfWeek),
            ],
            'month' => [
                'executions' => \App\Models\WorkflowExecution::where('created_at', '>=', $startOfMonth)->count(),
                'errors' => \App\Models\WorkflowExecution::where('status', 'error')->where('created_at', '>=', $startOfMonth)->count(),
                'success_rate' => $this->getSuccessRateForPeriod($startOfMonth),
            ],
            'total_workflows' => \App\Models\Workflow::count(),
            'active_workflows' => \App\Models\Workflow::where('status', 'active')->count(),
            'total_executions' => \App\Models\WorkflowExecution::count(),
        ];
    }

    private function getSuccessRateForPeriod(\DateTime $periodStart): float
    {
        $total = \App\Models\WorkflowExecution::where('created_at', '>=', $periodStart)->count();
        $success = \App\Models\WorkflowExecution::where('status', 'success')
            ->where('created_at', '>=', $periodStart)->count();

        return $total > 0 ? ($success / $total) * 100 : 100;
    }
}
