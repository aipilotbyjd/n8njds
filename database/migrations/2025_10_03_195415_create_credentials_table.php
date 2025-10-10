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
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('type'); // api_key, oauth2, http_basic, etc
            $table->text('data'); // Encrypted credential data
            $table->json('nodes_access')->nullable(); // Which nodes can access this credential
            $table->foreignId('owned_by')->constrained('users')->onDelete('cascade');
            $table->json('shared_with')->nullable(); // Users with whom credential is shared
            $table->uuid('encryption_key_id')->nullable(); // For advanced encryption
            $table->json('rotation_policy')->nullable(); // Automatic rotation settings
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamp('next_rotation_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // If credential has an expiration
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('owned_by');
            $table->index('next_rotation_at');
            $table->index(['owned_by', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
