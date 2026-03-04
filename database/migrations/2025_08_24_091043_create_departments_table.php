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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
             $table->string('name', 100);
                $table->string('slug', 50)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
            $table->timestamps();
             $table->softDeletes();
        });
        
         // Insert default departments
            $departments = [
                [
                    'name' => 'Share Directorate',
                    'slug' => 'share',
                    'description' => 'Handles share-related issues and inquiries',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Operation Directorate',
                    'slug' => 'operation',
                    'description' => 'Manages operational issues and support',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Plan and Strategy Directorate',
                    'slug' => 'plan-strategy',
                    'description' => 'Handles planning and strategic matters',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Marketing and Communication Directorate',
                    'slug' => 'marketing',
                    'description' => 'Manages marketing and communication issues',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'IT Directorate',
                    'slug' => 'it',
                    'description' => 'Handles IT support and technical issues',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Law Directorate',
                    'slug' => 'law',
                    'description' => 'Manages legal matters and compliance',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Human Resource Directorate',
                    'slug' => 'hr',
                    'description' => 'Handles HR-related issues and employee matters',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Compliance and Audit Directorate',
                    'slug' => 'compliance',
                    'description' => 'Manages compliance and audit requirements',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Finance Directorate',
                    'slug' => 'finance',
                    'description' => 'Handles financial matters and accounting issues',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ];

            DB::table('departments')->insert($departments);
        

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
