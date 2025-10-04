<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function status()
    {
        try {
            // Check if database is accessible
            DB::connection()->getPdo();
            $dbStatus = 'ok';
        } catch (\Exception $e) {
            $dbStatus = 'error';
        }

        // Check Redis connection if configured
        $redisStatus = 'unknown';
        try {
            if (app('redis')->connection()) {
                app('redis')->connection()->get('test');
                $redisStatus = 'ok';
            }
        } catch (\Exception $e) {
            $redisStatus = 'error';
        }

        // Get queue status
        $queueStatus = $this->getQueueStatus();

        $overallStatus = 'ok';
        if ($dbStatus !== 'ok' || $redisStatus !== 'ok' || $queueStatus !== 'ok') {
            $overallStatus = 'error';
        }

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'services' => [
                'database' => $dbStatus,
                'redis' => $redisStatus,
                'queue' => $queueStatus,
            ],
        ]);
    }

    public function metrics()
    {
        $monitoringService = new MonitoringService();
        
        return response()->json([
            'system_health' => $monitoringService->getSystemHealth(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function queue()
    {
        // Get queue statistics
        $queueStats = [];
        
        $connections = config('deployment.queues.connections', []);
        foreach ($connections as $name => $config) {
            if ($name === 'redis') {
                try {
                    $redis = app('redis')->connection();
                    $length = $redis->llen(config('queue.connections.redis.queue', 'default'));
                    $queueStats[$name] = [
                        'status' => 'ok',
                        'pending_jobs' => $length,
                    ];
                } catch (\Exception $e) {
                    $queueStats[$name] = [
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return response()->json([
            'status' => count($queueStats) > 0 ? 'ok' : 'unknown',
            'queues' => $queueStats,
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function getQueueStatus(): string
    {
        try {
            // Check if queue worker is running by checking a Redis connection
            if (config('queue.default') === 'redis') {
                $redis = app('redis')->connection();
                // Check if we can access the queue
                $redis->exists('laravel-queue-test');
                return 'ok';
            }
            return 'ok'; // If not using redis, assume it's ok
        } catch (\Exception $e) {
            return 'error';
        }
    }
}