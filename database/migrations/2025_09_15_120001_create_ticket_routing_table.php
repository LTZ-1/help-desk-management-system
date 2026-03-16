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
        Schema::create('ticket_routing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('to_department_id')->constrained('departments')->onDelete('cascade');
            $table->enum('routing_type', ['initial', 'forward'])->default('initial');
            $table->text('routing_notes')->nullable();
            $table->foreignId('routed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index('ticket_id');
            $table->index('from_department_id');
            $table->index('to_department_id');
            $table->index('routing_type');
            $table->index('routed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_routing');
    }
};
