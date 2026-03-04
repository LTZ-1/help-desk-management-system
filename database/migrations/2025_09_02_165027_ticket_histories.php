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
        Schema::create('ticket_histories', function (Blueprint $table) {
            $table->id();
              $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
                $table->string('action', 50);
                $table->text('description');
                $table->json('changes')->nullable()->comment('JSON object of changed fields and values');
            $table->timestamps();

             // Indexes
                $table->index('ticket_id');
                $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_histories');
    }
};
