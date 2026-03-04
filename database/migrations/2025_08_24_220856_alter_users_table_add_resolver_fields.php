<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add resolver/admin fields
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_resolver')->default(false);
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            
            // Indexes
            $table->index('is_admin');
            $table->index('is_resolver');
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['is_admin', 'is_resolver', 'department_id', 'phone', 'is_active']);
        });
    }
};