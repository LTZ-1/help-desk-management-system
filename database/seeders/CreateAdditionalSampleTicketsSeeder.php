<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Department;
use App\Models\TicketRouting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAdditionalSampleTicketsSeeder extends Seeder
{
    /**
     * Get realistic assignment type for integratable project
     * Ensures resolvers get tickets assigned and admins can self-assign
     */
    private function getRealisticAssignmentType($targetDepartment)
    {
        $resolversCount = User::where('department_id', $targetDepartment->id)
                               ->where('is_resolver', true)
                               ->count();
        
        $adminsCount = User::where('department_id', $targetDepartment->id)
                             ->where('is_admin', true)
                             ->count();

        // If no resolvers, admins should handle tickets
        if ($resolversCount === 0) {
            return rand(0, 2) === 0 ? 'unassigned' : 'self'; // 50% unassigned, 50% admin self-assign
        }

        // Weighted distribution for realistic scenario
        $weights = [
            'unassigned' => 10, // 10% unassigned (lower for additional tickets)
            'individual' => 60, // 60% assigned to resolvers
            'group' => 20,   // 20% group assignments
            'self' => 10    // 10% admin self-assignments
        ];

        $random = rand(1, 100);
        $cumulative = 0;
        
        foreach ($weights as $type => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $type;
            }
        }
        
        return 'individual'; // fallback
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users and departments
        $users = User::whereNotNull('department_id')->get();
        $departments = Department::all();
        
        if ($users->isEmpty() || $departments->isEmpty()) {
            $this->command->error('No users with departments or departments found. Please run users and departments seeders first.');
            return;
        }

        // Additional sample ticket data for more comprehensive testing
        $additionalTickets = [
            [
                'subject' => 'Laptop battery replacement',
                'description' => 'My laptop battery is not holding charge anymore. I need a replacement battery.',
                'category' => 'Hardware',
                'priority' => 'Medium',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'VPN connection issues',
                'description' => 'I cannot connect to the company VPN from home. It keeps timing out.',
                'category' => 'Network',
                'priority' => 'High',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'Microsoft Office license',
                'description' => 'I need a Microsoft Office license for my new computer.',
                'category' => 'Software',
                'priority' => 'Medium',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'Performance review process',
                'description' => 'I have questions about the annual performance review process and timeline.',
                'category' => 'HR Process',
                'priority' => 'Low',
                'recipant' => 'Human Resource Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'HR Branch',
            ],
            [
                'subject' => 'Payroll deduction inquiry',
                'description' => 'There is an unexpected deduction in my payroll. Can someone explain this?',
                'category' => 'Payroll',
                'priority' => 'High',
                'recipant' => 'Finance Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Finance Branch',
            ],
            [
                'subject' => 'Website downtime',
                'description' => 'The company website is down. Customers cannot access our services.',
                'category' => 'Infrastructure',
                'priority' => 'Critical',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'Data backup verification',
                'description' => 'I need to verify that our department data is being backed up properly.',
                'category' => 'Backup',
                'priority' => 'Medium',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'New employee onboarding',
                'description' => 'We have a new employee starting next week. What is the onboarding process?',
                'category' => 'Onboarding',
                'priority' => 'Medium',
                'recipant' => 'Human Resource Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'HR Branch',
            ],
            [
                'subject' => 'Budget approval request',
                'description' => 'I need to get approval for the Q3 marketing budget.',
                'category' => 'Budget',
                'priority' => 'High',
                'recipant' => 'Finance Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Finance Branch',
            ],
            [
                'subject' => 'Social media strategy',
                'description' => 'I need help developing a social media strategy for our new product launch.',
                'category' => 'Social Media',
                'priority' => 'Medium',
                'recipant' => 'Marketing and Communication Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Marketing Branch',
            ],
        ];

        // Create additional tickets and route them
        foreach ($additionalTickets as $index => $ticketData) {
            // Get a random regular user as requester
            $requester = $users->where('is_admin', false)->where('is_resolver', false)->random();
            
            // Find target department
            $targetDepartment = $departments->where('name', $ticketData['recipant'])->first();
            
            if (!$targetDepartment) {
                $this->command->warning("Department '{$ticketData['recipant']}' not found. Skipping ticket.");
                continue;
            }

            // Create the ticket
            $userDepartment = Department::find($requester->department_id);
            $userDepartmentName = $userDepartment ? $userDepartment->name : 'Unknown Department';
            
            $ticket = Ticket::create([
                'requester_id' => $requester->id,
                'requester_name' => $requester->name,
                'requester_email' => $requester->email,
                'requester_type' => $ticketData['requester_type'],
                'department' => $userDepartmentName,
                'brunch' => $ticketData['brunch'],
                'recipant' => $ticketData['recipant'],
                'subject' => $ticketData['subject'],
                'description' => $ticketData['description'],
                'category' => $ticketData['category'],
                'priority' => $ticketData['priority'],
                'status' => 'open',
                'ticket_number' => 'TKT-' . strtoupper(Str::random(8)) . '-' . ($index + 200), // Different numbering
                'assigned_department_id' => $targetDepartment->id,
            ]);

            // Create routing record
            TicketRouting::routeTicket($ticket, $targetDepartment);

            // Add to department ticket table
            $tableName = 'dept_' . $targetDepartment->slug . '_tickets';
            
            // More realistic assignment distribution for integratable project
            $assignmentType = $this->getRealisticAssignmentType($targetDepartment);
            $assignedResolver = null;
            $groupId = null;
            $assignedBy = null;
            
            switch ($assignmentType) {
                case 'unassigned':
                    $assignmentType = 'unassigned';
                    break;
                case 'individual':
                    $assignmentType = 'individual';
                    // Ensure we assign to actual resolvers, not admins
                    $assignedResolver = User::where('department_id', $targetDepartment->id)
                                        ->where('is_resolver', true)
                                        ->where('is_admin', false) // Prefer non-admin resolvers
                                        ->inRandomOrder()
                                        ->first()?->id;
                    $assignedBy = User::where('department_id', $targetDepartment->id)
                                      ->where('is_admin', true)
                                      ->first()?->id;
                    break;
                case 'group':
                    $assignmentType = 'group';
                    $groupId = 'GROUP-' . strtoupper(Str::random(8)) . '-' . time();
                    $assignedBy = User::where('department_id', $targetDepartment->id)
                                      ->where('is_admin', true)
                                      ->first()?->id;
                    break;
                case 'self':
                    $assignmentType = 'individual'; // Self assignment is still individual type
                    // Admin assigns to themselves (they are also resolvers)
                    $assignedResolver = User::where('department_id', $targetDepartment->id)
                                        ->where('is_admin', true)
                                        ->where('is_resolver', true)
                                        ->first()?->id;
                    $assignedBy = $assignedResolver;
                    break;
            }

            // Higher chance of resolved/in-progress tickets for additional data
            $ticketStatus = 'assigned';
            if ($assignmentType !== 'unassigned' && rand(1, 3) === 1) { // 33% chance of being resolved
                $ticketStatus = 'resolved';
            } elseif ($assignmentType !== 'unassigned' && rand(1, 2) === 1) { // 50% chance of being in progress
                $ticketStatus = 'in_progress';
            }

            DB::table($tableName)->insert([
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'assignment_type' => $assignmentType,
                'assigned_resolver_id' => $assignedResolver,
                'assignment_group_id' => $groupId,
                'assigned_by' => $assignedBy,
                'due_date' => now()->addDays(rand(1, 14)), // Longer due dates for additional tickets
                'assigned_at' => $assignmentType !== 'unassigned' ? now()->subHours(rand(1, 48)) : null,
                'position' => $index + 100,
                'created_at' => now()->subDays(rand(0, 60)), // Older tickets
                'updated_at' => now()->subHours(rand(1, 48)),
            ]);

            // Update main ticket if assigned
            if ($assignedResolver && $assignmentType !== 'group') {
                $ticket->assigned_resolver_id = $assignedResolver;
                $ticket->status = $ticketStatus;
                $ticket->save();
            } elseif ($assignmentType === 'group') {
                // For group assignments, set status to assigned
                $ticket->status = 'assigned';
                $ticket->save();
            }
        }

        $this->command->info('Additional sample tickets created and routed successfully!');
    }
}
