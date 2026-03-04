<?php
// database/migrations/2025_09_06_000000_add_assignment_fields_to_department_tables.php

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
        // Get all departments
        $departments = DB::table('departments')->get();
        
        foreach ($departments as $department) {
            $tableName = 'dept_' . $department->slug . '_tickets';
            
            // Check if table exists before modifying it
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Add assignment type field
                    if (!Schema::hasColumn($table->getTable(), 'assignment_type')) {
                        $table->enum('assignment_type', ['individual', 'group'])
                              ->nullable()
                              ->comment('Type of assignment: individual or group');
                    }
                    
                    // Add assigned resolver ID field
                    if (!Schema::hasColumn($table->getTable(), 'assigned_resolver_id')) {
                        $table->foreignId('assigned_resolver_id')
                              ->nullable()
                              ->constrained('users')
                              ->onDelete('set null')
                              ->comment('ID of the assigned resolver for individual assignments');
                    }
                    
                    // Add assignment group ID field
                    if (!Schema::hasColumn($table->getTable(), 'assignment_group_id')) {
                        $table->string('assignment_group_id')
                              ->nullable()
                              ->comment('Unique ID for group assignments');
                    }
                    
                    // Add due date field
                    if (!Schema::hasColumn($table->getTable(), 'due_date')) {
                        $table->timestamp('due_date')
                              ->nullable()
                              ->comment('Due date for the assignment');
                    }
                    
                    // Add assignment notes field
                    if (!Schema::hasColumn($table->getTable(), 'assignment_notes')) {
                        $table->text('assignment_notes')
                              ->nullable()
                              ->comment('Notes for the assignment');
                    }
                    
                    // Add forwarded flag field
                    if (!Schema::hasColumn($table->getTable(), 'forwarded')) {
                        $table->boolean('forwarded')
                              ->default(false)
                              ->comment('Whether the ticket has been forwarded');
                    }
                    
                    // Add forward notes field
                    if (!Schema::hasColumn($table->getTable(), 'forward_notes')) {
                        $table->text('forward_notes')
                              ->nullable()
                              ->comment('Notes for the forward action');
                    }
                    
                    // Add indexes for better performance
                    if (!Schema::hasColumn($table->getTable(), 'assignment_type')) {
                        $table->index('assignment_type');
                    }
                    
                    if (!Schema::hasColumn($table->getTable(), 'assigned_resolver_id')) {
                        $table->index('assigned_resolver_id');
                    }
                    
                    if (!Schema::hasColumn($table->getTable(), 'assignment_group_id')) {
                        $table->index('assignment_group_id');
                    }
                    
                    if (!Schema::hasColumn($table->getTable(), 'due_date')) {
                        $table->index('due_date');
                    }
                    
                    if (!Schema::hasColumn($table->getTable(), 'forwarded')) {
                        $table->index('forwarded');
                    }
                });
                
                // Log the table modification
                echo "Updated table: {$tableName}\n";
            } else {
                // Log if table doesn't exist
                echo "Table does not exist: {$tableName}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all departments
        $departments = DB::table('departments')->get();
        
        foreach ($departments as $department) {
            $tableName = 'dept_' . $department->slug . '_tickets';
            
            // Check if table exists before modifying it
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Drop foreign key constraint first
                    if (Schema::hasColumn($table->getTable(), 'assigned_resolver_id')) {
                        $table->dropForeign(['assigned_resolver_id']);
                    }
                    
                    // Drop columns
                    $columnsToDrop = [
                        'assignment_type',
                        'assigned_resolver_id',
                        'assignment_group_id',
                        'due_date',
                        'assignment_notes',
                        'forwarded',
                        'forward_notes'
                    ];
                    
                    foreach ($columnsToDrop as $column) {
                        if (Schema::hasColumn($table->getTable(), $column)) {
                            $table->dropColumn($column);
                        }
                    }
                    
                    // Drop indexes
                    $indexesToDrop = [
                        'assignment_type',
                        'assigned_resolver_id',
                        'assignment_group_id',
                        'due_date',
                        'forwarded'
                    ];
                    
                    foreach ($indexesToDrop as $index) {
                        if (Schema::hasIndex($table->getTable(), $index)) {
                            $table->dropIndex([$index]);
                        }
                    }
                });
                
                // Log the table modification
                echo "Reverted table: {$tableName}\n";
            } else {
                // Log if table doesn't exist
                echo "Table does not exist: {$tableName}\n";
            }
        }
    }
};