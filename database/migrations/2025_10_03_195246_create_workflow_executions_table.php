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
        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->uuid('execution_uuid')->unique(); // Human-readable execution ID
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('pending'); // pending, running, success, error, canceled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('mode')->default('manual'); // manual, trigger, scheduled
            $table->json('data')->nullable(); // Input data for the execution
            $table->json('error')->nullable(); // Error information if execution failed
            $table->integer('execution_time')->nullable(); // Execution time in seconds
            $table->json('node_executions')->nullable(); // Execution data per node
            $table->json('statistics')->nullable(); // Performance stats
            $table->smallInteger('priority')->default(0); // Execution priority
            $table->timestamps();

            $table->index('status');
            $table->index('started_at');
            $table->index('workflow_id');
            $table->index(['workflow_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_executions');
    }
};
