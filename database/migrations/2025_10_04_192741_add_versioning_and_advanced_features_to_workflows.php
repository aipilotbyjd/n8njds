<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add advanced fields to the workflows table
        Schema::table('workflows', function (Blueprint $table) {
            // Add UUID column if it doesn't exist
            if (!Schema::hasColumn('workflows', 'uuid')) {
                $table->uuid('uuid')->unique()->after('id');
                $table->index(['uuid'], 'workflows_uuid_index');
            }
            
            // Add description column
            if (!Schema::hasColumn('workflows', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
            
            // Add status column
            if (!Schema::hasColumn('workflows', 'status')) {
                $table->enum('status', ['active', 'inactive', 'draft'])->default('draft')->after('description');
            }
            
            // Add nodes column (for JSON storing workflow nodes)
            if (!Schema::hasColumn('workflows', 'nodes')) {
                $table->json('nodes')->nullable()->after('definition');
            }
            
            // Add connections column (for JSON storing workflow connections)
            if (!Schema::hasColumn('workflows', 'connections')) {
                $table->json('connections')->nullable()->after('nodes');
            }
            
            // Add settings column (for JSON storing workflow settings)
            if (!Schema::hasColumn('workflows', 'settings')) {
                $table->json('settings')->nullable()->after('connections');
            }
            
            // Add version column
            if (!Schema::hasColumn('workflows', 'version')) {
                $table->integer('version')->default(1)->after('settings');
            }
            
            // Add created_by and updated_by UUID columns
            if (!Schema::hasColumn('workflows', 'created_by')) {
                $table->uuid('created_by')->after('version');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('workflows', 'updated_by')) {
                $table->uuid('updated_by')->nullable()->after('created_by');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
            
            // Add last_executed_at timestamp
            if (!Schema::hasColumn('workflows', 'last_executed_at')) {
                $table->timestamp('last_executed_at')->nullable()->after('updated_by');
            }
            
            // Add execution_count column
            if (!Schema::hasColumn('workflows', 'execution_count')) {
                $table->integer('execution_count')->default(0)->after('last_executed_at');
            }
            
            // Add tag_ids column (for storing UUIDs of tags)
            if (!Schema::hasColumn('workflows', 'tag_ids')) {
                $table->json('tag_ids')->nullable()->after('execution_count');
            }
            
            // Add search_vector column for full-text search
            if (!Schema::hasColumn('workflows', 'search_vector')) {
                $table->text('search_vector')->nullable()->after('tag_ids');
            }
            
            // Update the foreign key relationship in workflows table
            // Replace user_id with created_by as the main reference
            if (Schema::hasColumn('workflows', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
        
        // Create the workflow_versions table as per the plan
        if (!Schema::hasTable('workflow_versions')) {
            Schema::create('workflow_versions', function (Blueprint $table) {
                $table->id();
                $table->uuid('workflow_id');
                $table->integer('version_number');
                $table->string('name');
                $table->string('description')->nullable();
                $table->json('nodes');
                $table->json('connections');
                $table->json('settings')->nullable();
                $table->uuid('created_by');
                $table->timestamp('created_at');
                $table->timestamp('committed_at'); // when version was created
                $table->text('commit_message')->nullable(); // reason for version change
                
                $table->foreign('workflow_id')->references('uuid')->on('workflows')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                
                $table->index(['workflow_id', 'version_number'], 'workflow_versions_workflow_version_index');
                $table->index(['created_at'], 'workflow_versions_created_at_index');
            });
        }
        
        // Create the workflow_events table for event sourcing as per the plan
        if (!Schema::hasTable('workflow_events')) {
            Schema::create('workflow_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('workflow_id');
                $table->string('event_type', 255);
                $table->json('event_data');
                $table->integer('aggregate_version');
                $table->timestamp('created_at');
                
                $table->index(['workflow_id'], 'workflow_events_workflow_id_index');
                $table->index(['event_type'], 'workflow_events_event_type_index');
                $table->index(['created_at'], 'workflow_events_created_at_index');
                $table->index(['aggregate_version'], 'workflow_events_aggregate_version_index');
            });
        }
        
        if (!Schema::hasTable('execution_events')) {
            Schema::create('execution_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('execution_id');
                $table->string('event_type', 255);
                $table->json('event_data');
                $table->bigInteger('sequence_number');
                $table->timestamp('created_at');
                
                $table->index(['execution_id'], 'execution_events_execution_id_index');
                $table->index(['event_type'], 'execution_events_event_type_index');
                $table->index(['sequence_number'], 'execution_events_sequence_number_index');
                $table->index(['created_at'], 'execution_events_created_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new tables first
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflow_events');
        Schema::dropIfExists('execution_events');
        
        // Restore the original workflow table structure
        Schema::table('workflows', function (Blueprint $table) {
            // Drop the added columns
            $table->dropIndex(['uuid']); // drops workflows_uuid_index
            $table->dropIndex(['status']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['last_executed_at']);
            $table->dropIndex(['execution_count']);
            
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            $table->dropColumn([
                'uuid', 'description', 'status', 'nodes', 'connections', 
                'settings', 'version', 'created_by', 'updated_by', 
                'last_executed_at', 'execution_count', 'tag_ids', 'search_vector'
            ]);
            
            // Restore the original user_id column
            $table->unsignedBigInteger('user_id')->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};