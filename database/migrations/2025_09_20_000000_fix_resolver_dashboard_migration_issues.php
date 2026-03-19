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
        // Fix assignment_type enum in main tickets table
        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'assignment_type')) {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN assignment_type ENUM('unassigned', 'individual', 'group', 'self', 'forwarded') DEFAULT 'unassigned'");
            echo "Updated tickets table assignment_type enum\n";
        }

        // Fix assignment_type enum in all department ticket tables
        $departments = DB::table('departments')->get();
        
        foreach ($departments as $department) {
            $tableName = 'dept_' . $department->slug . '_tickets';
            
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'assignment_type')) {
                // Use backticks to properly quote table names with hyphens
                DB::statement("ALTER TABLE `" . $tableName . "` MODIFY COLUMN assignment_type ENUM('unassigned', 'individual', 'group', 'self', 'forwarded') DEFAULT 'unassigned'");
                echo "Updated {$tableName} assignment_type enum\n";
            }
        }

        // Ensure all required fields exist in department ticket tables
        foreach ($departments as $department) {
            $tableName = 'dept_' . $department->slug . '_tickets';
            
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Add missing fields for resolver dashboard functionality
                    if (!Schema::hasColumn($table->getTable(), 'assigned_by')) {
                        $table->foreignId('assigned_by')
                              ->nullable()
                              ->constrained('users')
                              ->onDelete('set null')
                              ->comment('ID of the admin who assigned the ticket');
                    }
                    
                    if (!Schema::hasColumn($table->getTable(), 'assigned_at')) {
                        $table->timestamp('assigned_at')
                              ->nullable()
                              ->comment('When the ticket was assigned');
                    }
                    
                    if (!Schema::hasColumn($table->getTable(), 'position')) {
                        $table->integer('position')
                              ->default(0)
                              ->comment('Position for drag and drop reordering');
                    }
                    
                    if (!Schema::hasColumn($table->getTable(), 'forwarded_to_department_id')) {
                        $table->foreignId('forwarded_to_department_id')
                              ->nullable()
                              ->constrained('departments')
                              ->onDelete('set null')
                              ->comment('ID of the department the ticket was forwarded to');
                    }
                    
                    if (!Schema::hasColumn($table->getTable(), 'forward_notes')) {
                        $table->text('forward_notes')
                              ->nullable()
                              ->comment('Notes for the forward action');
                    }
                    
                    if (!Schema::hasColumn($table->getTable(), 'assignment_notes')) {
                        $table->text('assignment_notes')
                              ->nullable()
                              ->comment('Notes for the assignment');
                    }
                });
                
                echo "Ensured all required fields exist in {$tableName}\n";
            }
        }

        // Add missing indexes for better performance
        foreach ($departments as $department) {
            $tableName = 'dept_' . $department->slug . '_tickets';
            
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Add indexes if they don't exist - check for multiple possible index names
                    $indexes = [
                        'assigned_resolver_id' => ['assigned_resolver_id', 'idx_' . $table->getTable() . '_assigned_resolver_id'],
                        'assignment_group_id' => ['assignment_group_id', 'idx_' . $table->getTable() . '_assignment_group_id'], 
                        'due_date' => ['due_date', 'idx_' . $table->getTable() . '_due_date'],
                        'assigned_at' => ['assigned_at', 'idx_' . $table->getTable() . '_assigned_at'],
                        'assigned_by' => ['assigned_by', 'idx_' . $table->getTable() . '_assigned_by'],
                        'position' => ['position', 'idx_' . $table->getTable() . '_position']
                    ];
                    
                    foreach ($indexes as $column => $possibleNames) {
                        if (Schema::hasColumn($table->getTable(), $column)) {
                            $hasIndex = false;
                            foreach ($possibleNames as $indexName) {
                                if (Schema::hasIndex($table->getTable(), $indexName)) {
                                    $hasIndex = true;
                                    break;
                                }
                            }
                            
                            if (!$hasIndex) {
                                // Create a unique index name to avoid conflicts
                                $uniqueIndexName = 'idx_' . $table->getTable() . '_' . $column . '_' . time();
                                $table->index($column, $uniqueIndexName);
                            }
                        }
                    }
                });
                
                echo "Added missing indexes to {$tableName}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is designed to be safe and doesn't need to be reversed
        // as it only updates enum values and adds missing fields
        echo "Migration fix applied - no reversal needed\n";
    }
};
