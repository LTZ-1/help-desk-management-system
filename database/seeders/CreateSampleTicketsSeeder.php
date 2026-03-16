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

        // Sample ticket data
        $sampleTickets = [
            [
                'subject' => 'Computer not starting',
                'description' => 'My computer is not turning on. I have tried restarting it multiple times but nothing happens. The power light is not coming on.',
                'category' => 'Hardware',
                'priority' => 'High',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Main Office',
            ],
            [
                'subject' => 'Password reset request',
                'description' => 'I need to reset my password for the email system. I have forgotten my current password.',
                'category' => 'Software',
                'priority' => 'Medium',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Main Office',
            ],
            [
                'subject' => 'Leave application inquiry',
                'description' => 'I would like to inquire about the leave application process for annual leave. How many days in advance do I need to apply?',
                'category' => 'Policy',
                'priority' => 'Low',
                'recipant' => 'Human Resource Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'HR Department',
            ],
            [
                'subject' => 'Salary payment issue',
                'description' => 'I did not receive my salary for this month. Please check what happened with my payment.',
                'category' => 'Payment',
                'priority' => 'High',
                'recipant' => 'Finance Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Finance Department',
            ],
            [
                'subject' => 'Network connection problems',
                'description' => 'The internet connection in my office is very slow. Sometimes it disconnects completely.',
                'category' => 'Network',
                'priority' => 'Medium',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Main Office',
            ],
            [
                'subject' => 'Software installation request',
                'description' => 'I need to install Adobe Photoshop for my design work. Can you please help me with the installation?',
                'category' => 'Software',
                'priority' => 'Low',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Marketing Department',
            ],
            [
                'subject' => 'Employee benefits question',
                'description' => 'I have questions about the health insurance benefits provided by the company. Where can I get more information?',
                'category' => 'Benefits',
                'priority' => 'Medium',
                'recipant' => 'Human Resource Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'HR Department',
            ],
            [
                'subject' => 'Printer not working',
                'description' => 'The shared printer on our floor is not working. It shows an error message and does not print.',
                'category' => 'Hardware',
                'priority' => 'Medium',
                'recipant' => 'IT Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Main Office',
            ],
            [
                'subject' => 'Travel expense reimbursement',
                'description' => 'I need to submit my travel expenses for the business trip last week. What is the process for reimbursement?',
                'category' => 'Reimbursement',
                'priority' => 'Medium',
                'recipant' => 'Finance Directorate',
                'requester_type' => 'Staff',
                'brunch' => 'Finance Department',
            ],
            [
                'subject' => 'Email access issue',
                'description' => 'I cannot access my email from my phone. It keeps asking for password even though I am entering the correct one.',
                'category' => 'Email',
                'priority' => 'High',
                'recipant' => 'IT Directorate',
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
                'department_id' => $requester->department_id, // Add this required field
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
            
            // Randomly assign some tickets to demonstrate different assignment types
            $assignmentType = rand(0, 3);
            $assignedResolver = null;
            $groupId = null;
            $assignedBy = null;
            
            switch ($assignmentType) {
                case 0: // Unassigned
                    $assignmentType = 'unassigned';
                    break;
                case 1: // Individual assignment
                    $assignmentType = 'individual';
                    $assignedResolver = User::where('department_id', $targetDepartment->id)
                                        ->where('is_resolver', true)
                                        ->where('is_active', true)
                                        ->inRandomOrder()
                                        ->first()?->id;
                    $assignedBy = User::where('department_id', $targetDepartment->id)
                                      ->where('is_admin', true)
                                      ->first()?->id;
                    break;
                case 2: // Group assignment
                    $assignmentType = 'group';
                    $groupId = 'GROUP-' . strtoupper(Str::random(8)) . '-' . time();
                    $assignedBy = User::where('department_id', $targetDepartment->id)
                                      ->where('is_admin', true)
                                      ->first()?->id;
                    break;
                case 3: // Self assignment (admin working as resolver)
                    $assignmentType = 'self';
                    $assignedResolver = User::where('department_id', $targetDepartment->id)
                                        ->where('is_admin', true)
                                        ->first()?->id;
                    $assignedBy = $assignedResolver;
                    break;
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
                $ticket->status = $assignmentType === 'self' ? 'in_progress' : 'assigned';
                $ticket->save();
            }
        }

        $this->command->info('Sample tickets created and routed successfully!');
    }
}
