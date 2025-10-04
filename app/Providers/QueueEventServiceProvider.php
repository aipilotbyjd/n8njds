<?php

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use App\Services\MonitoringService;

// Register queue job event listeners for monitoring
Queue::before(function (JobProcessing $event) {
    // Log when a job starts processing
    $monitoring = new MonitoringService();
    $monitoring->logInfo("Job started processing: {$event->job->getJobId()}", [
        'job_name' => $event->job->getName(),
        'connection_name' => $event->connectionName,
        'queue' => $event->job->getQueue(),
    ]);
});

Queue::after(function (JobProcessed $event) {
    // Log when a job finishes processing
    $monitoring = new MonitoringService();
    $monitoring->logInfo("Job finished processing: {$event->job->getJobId()}", [
        'job_name' => $event->job->getName(),
        'connection_name' => $event->connectionName,
        'queue' => $event->job->getQueue(),
    ]);
});

// Register queue failure event listener
Queue::failing(function (\Illuminate\Queue\Events\JobFailed $event) {
    // Log when a job fails
    $monitoring = new MonitoringService();
    $monitoring->logError("Job failed: {$event->job->getJobId()}", [
        'job_name' => $event->job->getName(),
        'connection_name' => $event->connectionName,
        'queue' => $event->job->getQueue(),
        'exception' => $event->exception->getMessage(),
        'exception_trace' => $event->exception->getTraceAsString(),
    ]);
});