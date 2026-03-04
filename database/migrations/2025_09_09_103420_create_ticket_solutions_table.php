<?php
// database/migrations/2025_09_06_000001_create_ticket_solutions_table.php

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
        // Check if table already exists
        if (!Schema::hasTable('ticket_solutions')) {
            Schema::create('ticket_solutions', function (Blueprint $table) {
                $table->id()->comment('Unique identifier for the solution');
                
                // Foreign key to tickets table
                $table->foreignId('ticket_id')
                      ->constrained()
                      ->onDelete('cascade')
                      ->comment('Reference to the ticket being solved');
                
                // Foreign key to users table (resolver)
                $table->foreignId('resolver_id')
                      ->constrained('users')
                      ->onDelete('cascade')
                      ->comment('Reference to the user who solved the ticket');
                
                // Solution details
                $table->text('solution')
                      ->comment('The solution description or resolution details');
                
                // Attachment for solution documentation
                $table->string('attachment')
                      ->nullable()
                      ->comment('Path to attached solution documentation');
                
                // Resolver information (denormalized for historical reference)
                $table->string('resolver_name')
                      ->comment('Name of the resolver at the time of solution');
                
                $table->string('resolver_email')
                      ->comment('Email of the resolver at the time of solution');
                
                $table->string('department_name')
                      ->comment('Department name at the time of solution');
                
                $table->string('role')
                      ->comment('Role of the resolver at the time of solution');
                
                $table->boolean('is_active')
                      ->default(true)
                      ->comment('Whether the resolver was active at the time of solution');
                
                // Timestamps
                $table->timestamps();
                
                // Indexes for better performance
                $table->index('ticket_id');
                $table->index('resolver_id');
                $table->index('created_at');
                
                // Comment for the table
                $table->comment('Stores solutions and resolutions for tickets');
            });
            
            echo "Created table: ticket_solutions\n";
        } else {
            echo "Table already exists: ticket_solutions\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the table if it exists
        if (Schema::hasTable('ticket_solutions')) {
            Schema::dropIfExists('ticket_solutions');
            echo "Dropped table: ticket_solutions\n";
        } else {
            echo "Table does not exist: ticket_solutions\n";
        }
    }
};