<?php
// database/migrations/2025_09_09_110001_add_group_id_to_resolver_tickets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resolver_tickets', function (Blueprint $table) {
            $table->string('group_id')->nullable()
                  ->after('assignment_type')
                  ->index();
        });
    }

    public function down(): void
    {
        Schema::table('resolver_tickets', function (Blueprint $table) {
            $table->dropColumn('group_id');
        });
    }
};
