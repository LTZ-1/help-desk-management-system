<?php
// database/migrations/2025_09_06_000002_update_resolver_tickets_table.php

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
        // Check if table exists
        if (Schema::hasTable('resolver_tickets')) {
            Schema::table('resolver_tickets', function (Blueprint $table) {
                // Add assignment group ID for group assignments
                if (!Schema::hasColumn('resolver_tickets', 'assignment_group_id')) {
                    $table->string('assignment_group_id')
                          ->nullable()
                          ->after('assignment_type')
                          ->comment('Group ID for group assignments');
                }
                
                // Add notes field
                if (!Schema::hasColumn('resolver_tickets', 'notes')) {
                    $table->text('notes')
                          ->nullable()
                          ->after('assignment_group_id')
                          ->comment('Notes for the assignment');
                }
                
                // Add indexes
                if (!Schema::hasIndex('resolver_tickets', 'resolver_tickets_assignment_group_id_index')) {
                    $table->index('assignment_group_id');
                }
            });
            
            echo "Updated table: resolver_tickets\n";
        } else {
            echo "Table does not exist: resolver_tickets\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if table exists
        if (Schema::hasTable('resolver_tickets')) {
            Schema::table('resolver_tickets', function (Blueprint $table) {
                // Drop columns
                if (Schema::hasColumn('resolver_tickets', 'assignment_group_id')) {
                    $table->dropColumn('assignment_group_id');
                }
                
                if (Schema::hasColumn('resolver_tickets', 'notes')) {
                    $table->dropColumn('notes');
                }
                
                // Drop indexes
                if (Schema::hasIndex('resolver_tickets', 'resolver_tickets_assignment_group_id_index')) {
                    $table->dropIndex('resolver_tickets_assignment_group_id_index');
                }
            });
            
            echo "Reverted table: resolver_tickets\n";
        } else {
            echo "Table does not exist: resolver_tickets\n";
        }
    }
};