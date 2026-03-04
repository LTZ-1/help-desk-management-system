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
        Schema::table('tickets', function (Blueprint $table) {
            // Add sort_order column for drag and drop functionality
            $table->unsignedInteger('sort_order')->default(0)->after('due_date');
            
            // Add group_id column for group assignments
            $table->string('group_id')->nullable()->after('assigned_resolver_id');
            
            // Add assigned_at timestamp
            $table->timestamp('assigned_at')->nullable()->after('sort_order');
            
            // Add indexes for better performance
            $table->index('sort_order');
            $table->index('group_id');
            $table->index('assigned_at');
            
            // Add composite indexes for common queries
            $table->index(['assigned_department_id', 'status']);
            $table->index(['assigned_resolver_id', 'status']);
            $table->index(['assignment_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['assigned_department_id', 'status']);
            $table->dropIndex(['assigned_resolver_id', 'status']);
            $table->dropIndex(['assignment_type', 'status']);
            $table->dropIndex(['sort_order']);
            $table->dropIndex(['group_id']);
            $table->dropIndex(['assigned_at']);
            
            $table->dropColumn(['sort_order', 'group_id', 'assigned_at']);
        });
    }
};
