<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketHistory;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    /**
     * Assign ticket to individual resolver
     */
    public function assignToIndividual(Ticket $ticket, int $resolverId, ?int $adminId = null): array
    {
        return $this->performAssignment($ticket, [
            'action' => 'assign_individual',
            'resolver_id' => $resolverId,
            'admin_id' => $adminId ?? Auth::id(),
            'assignment_type' => 'individual'
        ]);
    }

    /**
     * Assign ticket to group of resolvers
     */
    public function assignToGroup(Ticket $ticket, array $resolverIds, ?int $adminId = null): array
    {
        if (count($resolverIds) < 2) {
            throw ValidationException::withMessages([
                'group' => 'At least two resolvers are required for group assignment'
            ]);
        }

        $groupId = $this->generateGroupId();

        return $this->performAssignment($ticket, [
            'action' => 'assign_group',
            'resolver_ids' => $resolverIds,
            'group_id' => $groupId,
            'admin_id' => $adminId ?? Auth::id(),
            'assignment_type' => 'group'
        ]);
    }

    /**
     * Assign ticket to self (admin)
     */
    public function assignToSelf(Ticket $ticket, int $adminId): array
    {
        return $this->performAssignment($ticket, [
            'action' => 'assign_myself',
            'resolver_id' => $adminId,
            'admin_id' => $adminId,
            'assignment_type' => 'individual'
        ]);
    }

    /**
     * Forward ticket to another department
     */
    public function forwardTicket(Ticket $ticket, int $targetDepartmentId, string $notes, ?int $adminId = null): array
    {
        $targetDepartment = Department::findOrFail($targetDepartmentId);
        $sourceDepartment = $ticket->assignedDepartment;

        return DB::transaction(function () use ($ticket, $targetDepartment, $sourceDepartment, $notes, $adminId) {
            // Prepare forwarding information
            $forwardingInfo = "\n\n--- Forwarded by {$sourceDepartment->name} ---\n{$notes}";
            
            // Update ticket with forwarding information
            $ticket->update([
                'assigned_department_id' => $targetDepartmentId,
                'assigned_resolver_id' => null,
                'assignment_type' => null,
                'group_id' => null,
                'status' => 'forwarded',
                'subject' => "**Forwarded** " . $ticket->subject,
                'description' => $ticket->description . $forwardingInfo
            ]);

            // Create assignment record
            TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'assigned_by' => $adminId ?? Auth::id(),
                'assigned_to' => null,
                'department_id' => $targetDepartmentId,
                'assignment_type' => 'forward',
                'notes' => $notes,
                'assigned_at' => now()
            ]);

            // Create history record
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $adminId ?? Auth::id(),
                'action' => 'forwarded',
                'description' => "Forwarded to {$targetDepartment->name}",
                'details' => json_encode([
                    'source_department_id' => $sourceDepartment->id,
                    'source_department_name' => $sourceDepartment->name,
                    'target_department_id' => $targetDepartmentId,
                    'target_department_name' => $targetDepartment->name,
                    'forward_notes' => $notes
                ])
            ]);

            return [
                'success' => true,
                'message' => "Ticket forwarded to {$targetDepartment->name}",
                'ticket' => $ticket->fresh(['assignedDepartment', 'assignedResolver'])
            ];
        });
    }

    /**
     * Bulk assign multiple tickets
     */
    public function bulkAssign(array $ticketIds, array $assignmentData): array
    {
        $results = [];
        $errors = [];

        DB::transaction(function () use ($ticketIds, $assignmentData, &$results, &$errors) {
            $tickets = Ticket::whereIn('id', $ticketIds)->get();

            foreach ($tickets as $ticket) {
                try {
                    if (!$this->canReassignTicket($ticket)) {
                        $errors[] = "Ticket #{$ticket->ticket_number} cannot be reassigned (status: {$ticket->status})";
                        continue;
                    }

                    $result = $this->performAssignment($ticket, $assignmentData);
                    $results[] = $result;
                } catch (\Exception $e) {
                    $errors[] = "Ticket #{$ticket->ticket_number}: " . $e->getMessage();
                }
            }
        });

        return [
            'success' => count($errors) === 0,
            'results' => $results,
            'errors' => $errors,
            'assigned_count' => count($results),
            'error_count' => count($errors)
        ];
    }

    /**
     * Perform the core assignment logic
     */
    private function performAssignment(Ticket $ticket, array $data): array
    {
        return DB::transaction(function () use ($ticket, $data) {
            // Check if ticket can be reassigned
            if (!$this->canReassignTicket($ticket)) {
                throw ValidationException::withMessages([
                    'ticket' => "Ticket #{$ticket->ticket_number} cannot be reassigned (status: {$ticket->status})"
                ]);
            }

            $adminId = $data['admin_id'];
            $action = $data['action'];

            // Update ticket based on assignment type
            $updateData = [
                'status' => 'assigned',
                'assigned_at' => now()
            ];

            switch ($action) {
                case 'assign_individual':
                    $resolverId = $data['resolver_id'];
                    $resolver = User::findOrFail($resolverId);

                    // Verify resolver belongs to same department (for non-forward assignments)
                    if ($ticket->assigned_department_id && $resolver->department_id !== $ticket->assigned_department_id) {
                        throw ValidationException::withMessages([
                            'resolver' => 'Resolver must belong to the same department as ticket'
                        ]);
                    }

                    $updateData['assigned_resolver_id'] = $resolverId;
                    $updateData['assignment_type'] = 'individual';
                    $updateData['group_id'] = null;

                    // Create assignment record
                    TicketAssignment::create([
                        'ticket_id' => $ticket->id,
                        'assigned_by' => $adminId,
                        'assigned_to' => $resolverId,
                        'department_id' => $ticket->assigned_department_id,
                        'assignment_type' => 'individual',
                        'assigned_at' => now()
                    ]);

                    // Create history record
                    TicketHistory::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $adminId,
                        'action' => 'assigned',
                        'description' => "Assigned to {$resolver->name}",
                        'details' => json_encode([
                            'resolver_id' => $resolverId,
                            'resolver_name' => $resolver->name,
                            'assignment_type' => 'individual'
                        ])
                    ]);

                    $message = "Ticket assigned to {$resolver->name}";
                    break;

                case 'assign_myself':
                    $resolverId = $data['resolver_id'];
                    $resolver = User::findOrFail($resolverId);

                    // Verify resolver belongs to same department (for non-forward assignments)
                    if ($ticket->assigned_department_id && $resolver->department_id !== $ticket->assigned_department_id) {
                        throw ValidationException::withMessages([
                            'resolver' => 'Resolver must belong to the same department as ticket'
                        ]);
                    }

                    $updateData['assigned_resolver_id'] = $resolverId;
                    $updateData['assignment_type'] = 'individual';
                    $updateData['group_id'] = null;
                    $updateData['status'] = 'in_progress'; // Set to in_progress for self-assignment

                    // Create assignment record
                    TicketAssignment::create([
                        'ticket_id' => $ticket->id,
                        'assigned_by' => $adminId,
                        'assigned_to' => $resolverId,
                        'department_id' => $ticket->assigned_department_id,
                        'assignment_type' => 'individual',
                        'assigned_at' => now()
                    ]);

                    // Create history record
                    TicketHistory::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $adminId,
                        'action' => 'assigned',
                        'description' => "Assigned to self ({$resolver->name}) for resolution",
                        'details' => json_encode([
                            'resolver_id' => $resolverId,
                            'resolver_name' => $resolver->name,
                            'assignment_type' => 'individual',
                            'self_assigned' => true
                        ])
                    ]);

                    $message = "Ticket assigned to you for resolution";
                    break;

                case 'assign_group':
                    $resolverIds = $data['resolver_ids'];
                    $groupId = $data['group_id'];

                    // Verify all resolvers belong to the same department
                    $resolvers = User::whereIn('id', $resolverIds)->get();
                    foreach ($resolvers as $resolver) {
                        if ($ticket->assigned_department_id && $resolver->department_id !== $ticket->assigned_department_id) {
                            throw ValidationException::withMessages([
                                'resolver' => "Resolver {$resolver->name} must belong to the same department as the ticket"
                            ]);
                        }
                    }

                    $updateData['assignment_type'] = 'group';
                    $updateData['group_id'] = $groupId;
                    $updateData['assigned_resolver_id'] = null; // Clear individual assignment

                    // Create assignment records for each group member
                    foreach ($resolverIds as $resolverId) {
                        TicketAssignment::create([
                            'ticket_id' => $ticket->id,
                            'assigned_by' => $adminId,
                            'assigned_to' => $resolverId,
                            'department_id' => $ticket->assigned_department_id,
                            'assignment_type' => 'group',
                            'group_id' => $groupId,
                            'assigned_at' => now()
                        ]);
                    }

                    // Create history record
                    $resolverNames = $resolvers->pluck('name')->join(', ');
                    TicketHistory::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $adminId,
                        'action' => 'assigned',
                        'description' => "Assigned to group: {$resolverNames}",
                        'details' => json_encode([
                            'group_id' => $groupId,
                            'resolver_ids' => $resolverIds,
                            'resolver_names' => $resolverNames,
                            'assignment_type' => 'group'
                        ])
                    ]);

                    $message = "Ticket assigned to group ({$resolverNames})";
                    break;

                default:
                    throw ValidationException::withMessages([
                        'action' => 'Invalid assignment action'
                    ]);
            }

            // Update due date if provided
            if (isset($data['due_date'])) {
                $updateData['due_date'] = $data['due_date'];
            }

            // Update the ticket
            $ticket->update($updateData);

            return [
                'success' => true,
                'message' => $message,
                'ticket' => $ticket->fresh(['assignedDepartment', 'assignedResolver'])
            ];
        });
    }

    /**
     * Generate unique group ID
     */
    private function generateGroupId(): string
    {
        do {
            $groupId = 'GRP-' . strtoupper(uniqid()) . '-' . time();
        } while (Ticket::where('group_id', $groupId)->exists());

        return $groupId;
    }

    /**
     * Check if ticket can be reassigned
     */
    public function canReassignTicket(Ticket $ticket): bool
    {
        return !in_array($ticket->status, ['closed', 'resolved']);
    }

    /**
     * Get group members for a ticket
     */
    public function getGroupMembers(Ticket $ticket): array
    {
        if ($ticket->assignment_type !== 'group' || !$ticket->group_id) {
            return [];
        }

        return User::join('ticket_assignments', 'users.id', '=', 'ticket_assignments.assigned_to')
            ->where('ticket_assignments.ticket_id', $ticket->id)
            ->where('ticket_assignments.group_id', $ticket->group_id)
            ->select(['users.id', 'users.name', 'users.email', 'users.is_admin'])
            ->get()
            ->toArray();
    }

    /**
     * Remove ticket from group assignment
     */
    public function removeFromGroup(Ticket $ticket, int $resolverId): array
    {
        if ($ticket->assignment_type !== 'group') {
            throw ValidationException::withMessages([
                'assignment' => 'Ticket is not assigned to a group'
            ]);
        }

        return DB::transaction(function () use ($ticket, $resolverId) {
            // Remove assignment record
            TicketAssignment::where('ticket_id', $ticket->id)
                ->where('assigned_to', $resolverId)
                ->where('group_id', $ticket->group_id)
                ->delete();

            // Check if group still has members
            $remainingMembers = TicketAssignment::where('ticket_id', $ticket->id)
                ->where('group_id', $ticket->group_id)
                ->count();

            if ($remainingMembers < 2) {
                // Convert to individual assignment with remaining member
                $remainingAssignment = TicketAssignment::where('ticket_id', $ticket->id)
                    ->where('group_id', $ticket->group_id)
                    ->first();

                if ($remainingAssignment) {
                    $ticket->update([
                        'assignment_type' => 'individual',
                        'assigned_resolver_id' => $remainingAssignment->assigned_to,
                        'group_id' => null
                    ]);

                    // Update remaining assignment record
                    $remainingAssignment->update([
                        'assignment_type' => 'individual',
                        'group_id' => null
                    ]);
                }

                $message = 'Group assignment converted to individual assignment';
            } else {
                $message = 'Resolver removed from group assignment';
            }

            // Create history record
            $resolver = User::find($resolverId);
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'removed_from_group',
                'description' => "Removed {$resolver->name} from group assignment",
                'details' => json_encode([
                    'removed_resolver_id' => $resolverId,
                    'removed_resolver_name' => $resolver->name,
                    'remaining_members' => $remainingMembers
                ])
            ]);

            return [
                'success' => true,
                'message' => $message,
                'ticket' => $ticket->fresh(['assignedDepartment', 'assignedResolver'])
            ];
        });
    }

    /**
     * Reassign ticket (change assignment)
     */
    public function reassignTicket(Ticket $ticket, array $newAssignmentData): array
    {
        if (!$this->canReassignTicket($ticket)) {
            throw ValidationException::withMessages([
                'ticket' => "Ticket cannot be reassigned (status: {$ticket->status})"
            ]);
        }

        return DB::transaction(function () use ($ticket, $newAssignmentData) {
            // Clear existing assignments
            TicketAssignment::where('ticket_id', $ticket->id)->delete();

            // Create history record for reassignment
            $oldAssignment = $ticket->assignment_type === 'group' 
                ? "Group ({$ticket->group_id})" 
                : ($ticket->assignedResolver?->name ?? 'Unassigned');

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'reassigned',
                'description' => "Reassigned from {$oldAssignment}",
                'details' => json_encode([
                    'old_assignment_type' => $ticket->assignment_type,
                    'old_resolver_id' => $ticket->assigned_resolver_id,
                    'old_group_id' => $ticket->group_id
                ])
            ]);

            // Perform new assignment
            return $this->performAssignment($ticket, $newAssignmentData);
        });
    }
}
