<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workflow_monitoring', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->string('metric_name'); // execution_time, success_rate, etc.
            $table->string('metric_type'); // gauge, counter, histogram
            $table->decimal('value', 15, 4);
            $table->json('labels')->nullable(); // Additional labels for the metric
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->foreign('workflow_id')->references('uuid')->on('workflows')->onDelete('cascade');
            $table->index(['workflow_id']);
            $table->index(['metric_name']);
            $table->index(['measured_at']);
        });

        Schema::create('system_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('level'); // error, warning, info, debug
            $table->string('channel'); // workflow, execution, node, etc.
            $table->string('message');
            $table->json('context')->nullable(); // Additional context data
            $table->text('stack_trace')->nullable(); // Full stack trace for errors
            $table->uuid('user_id')->nullable(); // User associated with the log
            $table->uuid('workflow_id')->nullable(); // Workflow associated with the log
            $table->uuid('execution_id')->nullable(); // Execution associated with the log
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('workflow_id')->references('uuid')->on('workflows')->onDelete('set null');
            $table->index(['level']);
            $table->index(['channel']);
            $table->index(['logged_at']);
            $table->index(['user_id']);
            $table->index(['workflow_id']);
        });

        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('metric_type'); // execution_time, memory_usage, etc.
            $table->uuid('workflow_id')->nullable();
            $table->uuid('execution_id')->nullable();
            $table->uuid('node_id')->nullable();
            $table->string('unit'); // seconds, bytes, etc.
            $table->decimal('value', 15, 4);
            $table->json('tags')->nullable(); // Additional tags for the metric
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->foreign('workflow_id')->references('uuid')->on('workflows')->onDelete('cascade');
            $table->foreign('execution_id')->references('id')->on('workflow_executions')->onDelete('cascade');
            $table->index(['metric_type']);
            $table->index(['measured_at']);
            $table->index(['workflow_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('workflow_monitoring');
    }
};
