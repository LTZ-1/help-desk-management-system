<?php
// database/migrations/2025_09_04_000000_create_resolver_tickets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolver_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->enum('assignment_type', ['individual', 'group'])->default('individual');
            $table->string('status')->default('assigned'); // assigned, in_progress, resolved
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->unique(['resolver_id', 'ticket_id']);
            $table->index('assignment_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolver_tickets');
    }
};