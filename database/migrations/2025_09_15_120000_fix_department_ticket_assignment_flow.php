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
        // Get all departments
        $departments = DB::table('departments')->get();
        
        foreach ($departments as $department) {
            $tableName = 'dept_' . $department->slug . '_tickets';
            
            // Check if table exists before modifying it
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($department) {
                    // Add assignment type field with proper enum values
                    if (!Schema::hasColumn($table->getTable(), 'assignment_type')) {
                        $table->enum('assignment_type', ['unassigned', 'individual', 'group', 'self', 'forwarded'])
                              ->default('unassigned')
                              ->comment('Type of assignment: unassigned, individual, group, self, forwarded');
                    } else {
                        // Update existing enum to include new values
                        DB::statement("ALTER TABLE " . $table->getTable() . " MODIFY COLUMN assignment_type ENUM('unassigned', 'individual', 'group', 'self', 'forwarded') DEFAULT 'unassigned'");
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
                    
                    // Add forwarded to department ID field
                    if (!Schema::hasColumn($table->getTable(), 'forwarded_to_department_id')) {
                        $table->foreignId('forwarded_to_department_id')
                              ->nullable()
                              ->constrained('departments')
                              ->onDelete('set null')
                              ->comment('ID of the department the ticket was forwarded to');
                    }
                    
                    // Add forward notes field
                    if (!Schema::hasColumn($table->getTable(), 'forward_notes')) {
                        $table->text('forward_notes')
                              ->nullable()
                              ->comment('Notes for the forward action');
                    }
                    
                    // Add position field for drag and drop reordering
                    if (!Schema::hasColumn($table->getTable(), 'position')) {
                        $table->integer('position')
                              ->default(0)
                              ->comment('Position for drag and drop reordering');
                    }
                    
                    // Add assigned at field
                    if (!Schema::hasColumn($table->getTable(), 'assigned_at')) {
                        $table->timestamp('assigned_at')
                              ->nullable()
                              ->comment('When the ticket was assigned');
                    }
                    
                    // Add assigned by field
                    if (!Schema::hasColumn($table->getTable(), 'assigned_by')) {
                        $table->foreignId('assigned_by')
                              ->nullable()
                              ->constrained('users')
                              ->onDelete('set null')
                              ->comment('ID of the admin who assigned the ticket');
                    }
                    
                    // Add indexes for better performance - only if they don't exist
                    // Check for existing indexes with different naming conventions
                    $existingIndexes = [
                        'assignment_type' => ['assignment_type', 'dept_' . $department->slug . '_tickets_assignment_type_index'],
                        'assigned_resolver_id' => ['assigned_resolver_id', 'dept_' . $department->slug . '_tickets_assigned_resolver_id_index'], 
                        'assignment_group_id' => ['assignment_group_id', 'dept_' . $department->slug . '_tickets_assignment_group_id_index'],
                        'due_date' => ['due_date', 'dept_' . $department->slug . '_tickets_due_date_index'],
                        'position' => ['position', 'dept_' . $department->slug . '_tickets_position_index'],
                        'assigned_at' => ['assigned_at', 'dept_' . $department->slug . '_tickets_assigned_at_index'],
                        'assigned_by' => ['assigned_by', 'dept_' . $department->slug . '_tickets_assigned_by_index']
                    ];
                    
                    foreach ($existingIndexes as $column => $possibleNames) {
                        $hasIndex = false;
                        foreach ($possibleNames as $indexName) {
                            if (Schema::hasIndex($table->getTable(), $indexName)) {
                                $hasIndex = true;
                                break;
                            }
                        }
                        
                        // If no existing index found, create a new one with unique name
                        if (!$hasIndex) {
                            $newIndexName = 'idx_' . $table->getTable() . '_' . $column . '_' . time();
                            $table->index($column, $newIndexName);
                        }
                    }
                });
                
                echo "Updated table: {$tableName}\n";
            } else {
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
                    // Drop foreign key constraints first
                    $foreignKeys = ['assigned_resolver_id', 'forwarded_to_department_id', 'assigned_by'];
                    
                    foreach ($foreignKeys as $foreignKey) {
                        if (Schema::hasColumn($table->getTable(), $foreignKey)) {
                            $table->dropForeign([$foreignKey]);
                        }
                    }
                    
                    // Drop columns
                    $columnsToDrop = [
                        'assignment_type',
                        'assigned_resolver_id',
                        'assignment_group_id',
                        'due_date',
                        'assignment_notes',
                        'forwarded_to_department_id',
                        'forward_notes',
                        'position',
                        'assigned_at',
                        'assigned_by'
                    ];
                    
                    foreach ($columnsToDrop as $column) {
                        if (Schema::hasColumn($table->getTable(), $column)) {
                            $table->dropColumn($column);
                        }
                    }
                });
                
                echo "Reverted table: {$tableName}\n";
            } else {
                echo "Table does not exist: {$tableName}\n";
            }
        }
    }
};
