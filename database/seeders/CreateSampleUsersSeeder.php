<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateSampleUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get departments
        $departments = Department::all();
        
        if ($departments->isEmpty()) {
            $this->command->error('No departments found. Please run departments seeder first.');
            return;
        }

        // Create sample users with different roles
        $users = [
            // Regular Users
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => Hash::make('password123'),
                'is_admin' => false,
                'is_resolver' => false,
                'department_id' => $departments->where('slug', 'it')->first()->id,
                'phone' => '+1234567890',
                'is_active' => true,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'password' => Hash::make('password123'),
                'is_admin' => false,
                'is_resolver' => false,
                'department_id' => $departments->where('slug', 'hr')->first()->id,
                'phone' => '+1234567891',
                'is_active' => true,
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@example.com',
                'password' => Hash::make('password123'),
                'is_admin' => false,
                'is_resolver' => false,
                'department_id' => $departments->where('slug', 'finance')->first()->id,
                'phone' => '+1234567892',
                'is_active' => true,
            ],

            // Department Admins
            [
                'name' => 'Admin IT',
                'email' => 'admin.it@example.com',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'is_resolver' => true, // Admins can also work as resolvers
                'department_id' => $departments->where('slug', 'it')->first()->id,
                'phone' => '+1234567893',
                'is_active' => true,
            ],
            [
                'name' => 'Admin HR',
                'email' => 'admin.hr@example.com',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'is_resolver' => true,
                'department_id' => $departments->where('slug', 'hr')->first()->id,
                'phone' => '+1234567894',
                'is_active' => true,
            ],
            [
                'name' => 'Admin Finance',
                'email' => 'admin.finance@example.com',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'is_resolver' => true,
                'department_id' => $departments->where('slug', 'finance')->first()->id,
                'phone' => '+1234567895',
                'is_active' => true,
            ],

            // Resolvers
            [
                'name' => 'Resolver IT 1',
                'email' => 'resolver.it1@example.com',
                'password' => Hash::make('resolver123'),
                'is_admin' => false,
                'is_resolver' => true,
                'department_id' => $departments->where('slug', 'it')->first()->id,
                'phone' => '+1234567896',
                'is_active' => true,
            ],
            [
                'name' => 'Resolver IT 2',
                'email' => 'resolver.it2@example.com',
                'password' => Hash::make('resolver123'),
                'is_admin' => false,
                'is_resolver' => true,
                'department_id' => $departments->where('slug', 'it')->first()->id,
                'phone' => '+1234567897',
                'is_active' => true,
            ],
            [
                'name' => 'Resolver HR 1',
                'email' => 'resolver.hr1@example.com',
                'password' => Hash::make('resolver123'),
                'is_admin' => false,
                'is_resolver' => true,
                'department_id' => $departments->where('slug', 'hr')->first()->id,
                'phone' => '+1234567898',
                'is_active' => true,
            ],
            [
                'name' => 'Resolver Finance 1',
                'email' => 'resolver.finance1@example.com',
                'password' => Hash::make('resolver123'),
                'is_admin' => false,
                'is_resolver' => true,
                'department_id' => $departments->where('slug', 'finance')->first()->id,
                'phone' => '+1234567899',
                'is_active' => true,
            ],
        ];

        // Insert users
        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('Sample users created successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('Regular Users: john.doe@example.com / password123');
        $this->command->info('Department Admins: admin.it@example.com / admin123');
        $this->command->info('Resolvers: resolver.it1@example.com / resolver123');
    }
}
