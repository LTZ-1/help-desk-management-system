<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Department;
use App\Models\TicketRouting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSampleTicketsSeeder extends Seeder
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
            'unassigned' => 15, // 15% unassigned
            'individual' => 50, // 50% assigned to resolvers
            'group' => 20,   // 20% group assignments
            'self' => 15    // 15% admin self-assignments
        ];

        $random = rand(1, 100);
        $cumulative = 0;
        
        foreach ($weights as $type => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $type;
            }
        }
        
        return 'individual'; 
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users and departments
        $users = User::whereNotNull('department_id')->get(); // Only get users with department_id
        $departments = Department::all();
        
        if ($users->isEmpty() || $departments->isEmpty()) {
            $this->command->error('No users with departments or departments found. Please run users and departments seeders first.');
            return;
        }

        // Sample ticket data - Updated to match actual department names
        $sampleTickets = [
            [
                'subject' => 'Computer not starting',
                'description' => 'My computer is not turning on. I have tried restarting it multiple times but nothing happens. The power light is not coming on.',
                'category' => 'Hardware',
                'priority' => 'High',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'Password reset request',
                'description' => 'I need to reset my password for the email system. I have forgotten my current password.',
                'category' => 'Software',
                'priority' => 'Medium',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'Leave application inquiry',
                'description' => 'I would like to inquire about the leave application process for annual leave. How many days in advance do I need to apply?',
                'category' => 'Policy',
                'priority' => 'Low',
                'recipant' => 'Human Resource Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'HR Branch',
            ],
            [
                'subject' => 'Salary payment issue',
                'description' => 'I did not receive my salary for this month. Please check what happened with my payment.',
                'category' => 'Payment',
                'priority' => 'High',
                'recipant' => 'Finance Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Finance Branch',
            ],
            [
                'subject' => 'Network connection problems',
                'description' => 'The internet connection in my office is very slow. Sometimes it disconnects completely.',
                'category' => 'Network',
                'priority' => 'Medium',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'Software installation request',
                'description' => 'I need to install Adobe Photoshop for my design work. Can you please help me with the installation?',
                'category' => 'Software',
                'priority' => 'Low',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Marketing Branch',
            ],
            [
                'subject' => 'Employee benefits question',
                'description' => 'I have questions about the health insurance benefits provided by the company. Where can I get more information?',
                'category' => 'Benefits',
                'priority' => 'Medium',
                'recipant' => 'Human Resource Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'HR Branch',
            ],
            [
                'subject' => 'Printer not working',
                'description' => 'The shared printer on our floor is not working. It shows an error message and does not print.',
                'category' => 'Hardware',
                'priority' => 'Medium',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'Travel expense reimbursement',
                'description' => 'I need to submit my travel expenses for the business trip last week. What is the process for reimbursement?',
                'category' => 'Reimbursement',
                'priority' => 'Medium',
                'recipant' => 'Finance Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Finance Branch',
            ],
            [
                'subject' => 'Email access issue',
                'description' => 'I cannot access my email from my phone. It keeps asking for password even though I am entering the correct one.',
                'category' => 'Email',
                'priority' => 'High',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'IT Branch',
            ],
            [
                'subject' => 'Share transfer process',
                'description' => 'I need to understand the process for transferring shares to another family member. What documents are required?',
                'category' => 'Shares',
                'priority' => 'Medium',
                'recipant' => 'Share Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Main Office',
            ],
            [
                'subject' => 'Operational workflow issue',
                'description' => 'Our team is experiencing delays in the operational workflow. Can someone help optimize our processes?',
                'category' => 'Operations',
                'priority' => 'High',
                'recipant' => 'Operation Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Main Office',
            ],
            [
                'subject' => 'Strategic planning consultation',
                'description' => 'We need guidance on strategic planning for the next quarter. Who should we contact?',
                'category' => 'Strategy',
                'priority' => 'Low',
                'recipant' => 'Plan and Strategy Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Main Office',
            ],
            [
                'subject' => 'Marketing campaign approval',
                'description' => 'I need to get approval for a new marketing campaign. What is the process?',
                'category' => 'Marketing',
                'priority' => 'Medium',
                'recipant' => 'Marketing and Communication Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Marketing Branch',
            ],
            [
                'subject' => 'Legal contract review',
                'description' => 'I need a legal contract reviewed before signing with a new vendor.',
                'category' => 'Legal',
                'priority' => 'High',
                'recipant' => 'Law Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Main Office',
            ],
            [
                'subject' => 'Compliance audit preparation',
                'description' => 'We are preparing for a compliance audit. What documents do we need to gather?',
                'category' => 'Compliance',
                'priority' => 'Medium',
                'recipant' => 'Compliance and Audit Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Main Office',
            ],
        ];

        // Create tickets and route them
        foreach ($sampleTickets as $index => $ticketData) {
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
                'ticket_number' => 'TKT-' . strtoupper(Str::random(8)) . '-' . ($index + 1),
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

            // Set realistic status based on assignment
            $ticketStatus = 'open';
            if ($assignmentType !== 'unassigned' && rand(1, 3) === 1) { // 33% chance of being in progress
                $ticketStatus = 'in_progress';
            } elseif ($assignmentType !== 'unassigned' && rand(1, 5) === 1) { // 20% chance of being resolved
                $ticketStatus = 'resolved';
            } elseif ($assignmentType !== 'unassigned') {
                $ticketStatus = 'assigned';
            }

            DB::table($tableName)->insert([
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'assignment_type' => $assignmentType,
                'assigned_resolver_id' => $assignedResolver,
                'assignment_group_id' => $groupId,
                'assigned_by' => $assignedBy,
                'due_date' => now()->addDays(rand(3, 7)),
                'assigned_at' => $assignmentType !== 'unassigned' ? now()->subHours(rand(1, 24)) : null,
                'position' => $index + 1,
                'created_at' => now()->subDays(rand(0, 30)),
                'updated_at' => now()->subHours(rand(1, 24)),
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

        $this->command->info('Sample tickets created and routed successfully!');
    }
}
