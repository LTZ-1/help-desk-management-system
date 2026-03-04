<?php
// File: 2025_08_27_000000_create_department_ticket_tables.php

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
            
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
                $table->string('ticket_number');
                $table->timestamps();
                
                // Indexes for better performance
                $table->index('ticket_id');
                $table->index('ticket_number');
                $table->index('created_at');
            });
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
            Schema::dropIfExists($tableName);
        }
    }
};