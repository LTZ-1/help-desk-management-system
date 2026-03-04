
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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
             $table->string('ticket_number')->unique()->nullable()->comment('Auto-generated ticket ID');
            $table->string('brunch');
            $table->string('department');
            $table->string('recipant')->nullable();
            $table->string('subject');
            $table->text('description');
            $table->string('category');
            $table->string('priority');
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('requester_id');
            $table->string('requester_type');
            $table->string('requester_name');
            $table->string('requester_email');
             
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
             $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->onDelete('set null');
                $table->foreignId('assigned_resolver_id')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('due_date')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('closed_at')->nullable();
            $table->timestamps();
             $table->softDeletes();
             // Indexes for better performance
            $table->index('category');
            $table->index('priority');
            $table->index('status');
             $table->index('assigned_department_id');
                $table->index('assigned_resolver_id');
                $table->index('due_date');
                $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

