<?php
// database/migrations/2025_09_04_000001_add_assignment_type_to_tickets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('assignment_type', ['individual', 'group'])
                  ->default('individual')
                  ->after('assigned_resolver_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('assignment_type');
        });
    }
};