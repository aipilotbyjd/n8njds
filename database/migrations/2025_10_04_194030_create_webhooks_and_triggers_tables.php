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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->string('endpoint')->unique(); // The webhook URL
            $table->string('method')->default('POST'); // HTTP method
            $table->text('secret')->nullable(); // Secret for verifying webhook signatures
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->json('settings')->nullable(); // Additional webhook settings
            $table->timestamps();

            $table->foreign('workflow_id')->references('uuid')->on('workflows')->onDelete('cascade');
            $table->index(['workflow_id']);
            $table->index(['is_active']);
            $table->index(['last_triggered_at']);
        });

        Schema::create('triggers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->string('type'); // webhook, schedule, event, manual
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('configuration'); // Trigger-specific configuration
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_executed_at')->nullable();
            $table->integer('execution_count')->default(0);
            $table->json('settings')->nullable(); // Additional trigger settings
            $table->timestamps();

            $table->foreign('workflow_id')->references('uuid')->on('workflows')->onDelete('cascade');
            $table->index(['workflow_id']);
            $table->index(['type']);
            $table->index(['is_active']);
            $table->index(['last_executed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triggers');
        Schema::dropIfExists('webhooks');
    }
};
