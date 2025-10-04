<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deployment Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines settings for deploying the application in
    | different environments with scaling capabilities.
    |
    */

    'environments' => [
        'local' => [
            'url' => env('APP_URL', 'http://localhost'),
            'workers' => 1,
            'max_jobs' => 1000,
            'memory' => '256M',
        ],
        'staging' => [
            'url' => env('STAGING_URL', 'https://staging.n8n-clone.example.com'),
            'workers' => 2,
            'max_jobs' => 5000,
            'memory' => '512M',
            'queue_connections' => ['redis'],
        ],
        'production' => [
            'url' => env('PROD_URL', 'https://n8n-clone.example.com'),
            'workers' => 4,
            'max_jobs' => 10000,
            'memory' => '1G',
            'queue_connections' => ['redis', 'database'],
            'load_balancer' => [
                'enabled' => true,
                'algorithm' => 'round-robin',
                'health_check_path' => '/health',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scaling Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for auto-scaling based on load metrics.
    |
    */
    'scaling' => [
        'enabled' => env('SCALING_ENABLED', false),
        'metrics' => [
            'queue_length' => [
                'threshold' => 50, // Scale up when queue has more than 50 jobs
                'scale_up_count' => 1,
            ],
            'cpu_usage' => [
                'threshold' => 80, // Scale up when CPU usage is above 80%
                'scale_up_count' => 2,
            ],
            'memory_usage' => [
                'threshold' => 85, // Scale up when memory usage is above 85%
                'scale_up_count' => 1,
            ],
        ],
        'limits' => [
            'min_instances' => 1,
            'max_instances' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration for Scaling
    |--------------------------------------------------------------------------
    |
    | Configuration for handling high-volume queue processing.
    |
    */
    'queues' => [
        'default_connection' => env('QUEUE_CONNECTION', 'redis'),
        'connections' => [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
                'queue' => env('REDIS_QUEUE', 'default'),
                'retry_after' => 90,
                'block_for' => null,
                'after_commit' => false,
            ],
            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90,
                'after_commit' => false,
            ],
        ],
        'workers' => [
            'sleep' => 3,
            'tries' => 3,
            'max_attempts' => 5,
            'timeout' => 300, // 5 minutes timeout
            'memory_limit' => 512, // 512MB memory limit
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Scaling Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for database scaling and connection pooling.
    |
    */
    'database' => [
        'read_replicas' => [
            'enabled' => env('DB_READ_REPLICAS_ENABLED', false),
            'connections' => [
                'read' => [
                    'host' => env('DB_READ_HOST', 'localhost'),
                    'port' => env('DB_READ_PORT', 5432),
                    'database' => env('DB_READ_DATABASE', ''),
                    'username' => env('DB_READ_USERNAME', ''),
                    'password' => env('DB_READ_PASSWORD', ''),
                ],
            ],
        ],
        'connection_pooling' => [
            'enabled' => env('DB_CONNECTION_POOLING', false),
            'pool_size' => env('DB_POOL_SIZE', 20),
            'max_overflow' => env('DB_MAX_OVERFLOW', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-level caching strategy.
    |
    */
    'caching' => [
        'enabled' => true,
        'providers' => [
            'local' => [
                'driver' => 'file',
                'path' => storage_path('framework/cache/data'),
            ],
            'distributed' => [
                'driver' => 'redis',
                'connection' => 'cache',
            ],
            'cdn' => [
                'driver' => env('CACHE_CDN_DRIVER', 'cloudfront'),
                'url' => env('CACHE_CDN_URL', ''),
            ],
        ],
        'ttl' => [
            'short' => 300, // 5 minutes
            'medium' => 3600, // 1 hour
            'long' => 86400, // 24 hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for application monitoring and health checks.
    |
    */
    'monitoring' => [
        'health_check' => [
            'enabled' => true,
            'endpoints' => [
                'status' => '/api/v1/health/status',
                'metrics' => '/api/v1/health/metrics',
                'queue' => '/api/v1/health/queue',
            ],
        ],
        'metrics_collection' => [
            'enabled' => env('METRICS_ENABLED', true),
            'providers' => [
                'prometheus' => [
                    'enabled' => true,
                    'endpoint' => '/metrics',
                ],
                'custom_monitoring' => [
                    'enabled' => true,
                    'class' => \App\Domains\Billing\Services\MonitoringService::class,
                ],
            ],
        ],
    ],
];