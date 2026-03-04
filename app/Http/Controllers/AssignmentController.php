<?php

namespace App\Http\Controllers;

use App\Models\TicketAssignment;
use App\Models\Ticket;
use App\Models\Resolver;
use App\Models\TicketHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends Controller
{
    /**
     * Get assignment history for a ticket
     */
    public function index(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $resolver = $request->user();

        // Verify the ticket belongs to resolver's department
        if ($ticket->assigned_department_id !== $resolver->department_id) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Ticket does not belong to your department'
            ], 403);
        }

        $assignments = TicketAssignment::with(['assigner', 'assignee'])
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'assignments' => $assignments
        ]);
    }

    /**
     * Get resolver's assignment history
     */
    public function resolverAssignments(Request $request, $resolverId)
    {
        $resolver = $request->user();

        // Resolvers can only see their own assignments or their department's assignments if admin
        if ($resolverId != $resolver->id && !$resolver->is_admin) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You can only view your own assignments'
            ], 403);
        }

        $assignments = TicketAssignment::with(['ticket', 'assigner', 'assignee'])
            ->where('assigned_to', $resolverId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'assignments' => $assignments
        ]);
    }

    /**
     * Update assignment due date or notes
     */
    public function update(Request $request, $assignmentId)
    {
        $assignment = TicketAssignment::with(['ticket'])->findOrFail($assignmentId);
        $resolver = $request->user();

        // Verify the assignment belongs to resolver's department
        if ($assignment->ticket->assigned_department_id !== $resolver->department_id) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Assignment does not belong to your department'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'due_date' => 'sometimes|date|after:now',
            'notes' => 'sometimes|nullable|string',
            'completed_at' => 'sometimes|nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $assignment->update($validator->validated());

        // Log the assignment update
        TicketHistory::log(
            $assignment->ticket_id,
            $resolver->id,
            'assignment_updated',
            "Assignment updated",
            $validator->validated()
        );

        return response()->json([
            'message' => 'Assignment updated successfully',
            'assignment' => $assignment->fresh()
        ]);
    }

    /**
     * Get overdue assignments
     */
    public function overdue(Request $request)
    {
        $resolver = $request->user();

        $overdueAssignments = TicketAssignment::with(['ticket', 'assigner', 'assignee'])
            ->where('due_date', '<', now())
            ->whereNull('completed_at')
            ->whereHas('ticket', function($query) use ($resolver) {
                $query->where('assigned_department_id', $resolver->department_id);
            })
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'overdue_assignments' => $overdueAssignments,
            'count' => $overdueAssignments->count()
        ]);
    }

    /**
     * Mark assignment as completed
     */
    public function complete(Request $request, $assignmentId)
    {
        $assignment = TicketAssignment::with(['ticket'])->findOrFail($assignmentId);
        $resolver = $request->user();

        // Verify the assignment belongs to resolver's department
        if ($assignment->ticket->assigned_department_id !== $resolver->department_id) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Assignment does not belong to your department'
            ], 403);
        }

        $assignment->completed_at = now();
        $assignment->save();

        // Log the assignment completion
        TicketHistory::log(
            $assignment->ticket_id,
            $resolver->id,
            'assignment_completed',
            "Assignment marked as completed"
        );

        return response()->json([
            'message' => 'Assignment marked as completed',
            'assignment' => $assignment->fresh()
        ]);
    }
}