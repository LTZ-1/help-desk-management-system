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
        Schema::table('users', function (Blueprint $table) {
            // Add is_none field if it doesn't exist
            if (!Schema::hasColumn('users', 'is_none')) {
                $table->boolean('is_none')->default(false)->after('is_resolver');
            }
            
            // Add branch field if it doesn't exist
            if (!Schema::hasColumn('users', 'branch')) {
                $table->string('branch')->nullable()->after('department_id');
            }
            
            // Ensure department_id exists (if not already there)
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null')->after('email_verified_at');
            }
            
            // Ensure is_admin exists (if not already there)
            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('department_id');
            }
            
            // Ensure is_resolver exists (if not already there)
            if (!Schema::hasColumn('users', 'is_resolver')) {
                $table->boolean('is_resolver')->default(false)->after('is_admin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove the columns we added
            $table->dropColumn(['is_none', 'branch']);
            
            // Optional: Remove other columns if they were added by this migration
            if (Schema::hasColumn('users', 'department_id') && !Schema::hasColumn('users', 'department_id_before_migration')) {
                $table->dropConstrainedForeignId('department_id');
            }
            
            if (Schema::hasColumn('users', 'is_admin') && !Schema::hasColumn('users', 'is_admin_before_migration')) {
                $table->dropColumn('is_admin');
            }
            
            if (Schema::hasColumn('users', 'is_resolver') && !Schema::hasColumn('users', 'is_resolver_before_migration')) {
                $table->dropColumn('is_resolver');
            }
        });
    }
};