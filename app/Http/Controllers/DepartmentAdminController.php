<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Department;
use App\Services\TicketService;
use App\Services\AssignmentService;
use App\Services\ResolverService;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepartmentAdminController extends Controller
{
    protected $ticketService;
    protected $assignmentService;
    protected $resolverService;

    public function __construct(
        TicketService $ticketService,
        AssignmentService $assignmentService,
        ResolverService $resolverService
    ) {
        $this->ticketService = $ticketService;
        $this->assignmentService = $assignmentService;
        $this->resolverService = $resolverService;
    }

    /**
     * Display the department admin dashboard
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        if (!$user->department_id) {
            return redirect()->route('dashboard')->with('error', 'No department assigned');
        }

        $department = Department::findOrFail($user->department_id);
        
        // Get department statistics
        $statistics = $this->ticketService->getDepartmentStatistics($user->department_id);
        
        // Get chart data
        $chartData = $this->ticketService->getDepartmentChartData($user->department_id, '90d');

        return Inertia::render('DepartmentAdmin/Dashboard', [
            'department' => $department,
            'statistics' => $statistics,
            'chartData' => $chartData
        ]);
    }

    /**
     * Get tickets for the department admin
     */
    public function getTickets(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->department_id) {
            return response()->json(['error' => 'No department assigned'], 403);
        }

        $filters = $request->all();
        $tickets = $this->ticketService->getDepartmentTickets($user->department_id, $filters);

        return response()->json([
            'tickets' => $tickets,
            'filters' => $filters
        ]);
    }

    /**
     * Get "My Tickets" (assigned to current admin)
     */
    public function getMyTickets(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->department_id) {
            return response()->json(['error' => 'No department assigned'], 403);
        }

        try {
            // Get the department table name
            $department = Department::findOrFail($user->department_id);
            $tableName = 'dept_' . $department->slug . '_tickets';
            
            // Get tickets from department table where assigned to this admin
            $query = DB::table($tableName)
                ->join('tickets', 'tickets.id', '=', $tableName . '.ticket_id')
                ->select(
                    'tickets.*',
                    $tableName . '.assignment_type as dept_assignment_type',
                    $tableName . '.assigned_resolver_id',
                    $tableName . '.assignment_group_id',
                    $tableName . '.assigned_at',
                    $tableName . '.assigned_by',
                    $tableName . '.due_date as dept_due_date'
                )
                ->where(function($query) use ($user, $tableName) {
                    // Include tickets where this admin is the assigned resolver
                    $query->where($tableName . '.assigned_resolver_id', $user->id);
                })
                ->where('tickets.assigned_department_id', $user->department_id);

            // Apply filters if provided
            if ($request->status && $request->status !== '') {
                $query->where('tickets.status', $request->status);
            }
            if ($request->priority && $request->priority !== '') {
                $query->where('tickets.priority', $request->priority);
            }
            if ($request->category && $request->category !== '') {
                $query->where('tickets.category', $request->category);
            }
            if ($request->search && $request->search !== '') {
                $query->where(function($q) use ($request) {
                    $q->where('tickets.ticket_number', 'like', '%' . $request->search . '%')
                      ->orWhere('tickets.subject', 'like', '%' . $request->search . '%');
                });
            }

            // Order by latest first
            $query->orderBy('tickets.created_at', 'desc');

            $tickets = $query->get();

            // Transform the data to match frontend expectations
            $formattedTickets = $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'category' => $ticket->category,
                    'created_at' => $ticket->created_at,
                    'due_date' => $ticket->dept_due_date, // Use department table due date
                    'assigned_to' => $ticket->assigned_resolver_id,
                    'assignment_type' => $ticket->dept_assignment_type, // Use department table assignment type
                    'resolver_id' => $ticket->assigned_resolver_id,
                    'assigned_resolver_id' => $ticket->assigned_resolver_id,
                    'group_id' => $ticket->assignment_group_id,
                    'assigned_at' => $ticket->assigned_at,
                    'assigned_by' => $ticket->assigned_by,
                ];
            });

            return response()->json([
                'tickets' => $formattedTickets,
                'filters' => $request->all()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching my tickets: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch tickets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department resolvers
     */
    public function getResolvers(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->department_id) {
            return response()->json(['error' => 'No department assigned'], 403);
        }

        $filters = $request->all();
        $resolvers = $this->resolverService->getDepartmentResolvers($user->department_id, $filters);

        return response()->json([
            'resolvers' => $resolvers,
            'filters' => $filters
        ]);
    }

    /**
     * Get available resolvers for assignment
     */
    public function getAvailableResolvers(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->department_id) {
            return response()->json(['error' => 'No department assigned'], 403);
        }

        $resolvers = $this->ticketService->getDepartmentResolvers($user->department_id);

        return response()->json(['resolvers' => $resolvers]);
    }

    /**
     * Assign ticket (individual, group, self, or forward)
     */
    public function assignTicket(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        // Validate department access
        if ($ticket->assigned_department_id !== $user->department_id) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'action' => 'required|in:assign_individual,assign_group,assign_myself,forward',
            'resolver_id' => 'required_if:action,assign_individual,assign_myself|exists:users,id',
            'resolver_ids' => 'required_if:action,assign_group|array|min:2',
            'resolver_ids.*' => 'exists:users,id',
            'forward_to_department_id' => 'required_if:action,forward|exists:departments,id',
            'forward_notes' => 'required_if:action,forward|string',
            'due_date' => 'nullable|date|after_or_equal:today'
        ]);

        try {
            switch ($request->action) {
                case 'assign_individual':
                    $result = $this->assignmentService->assignToIndividual($ticket, $request->resolver_id);
                    break;
                    
                case 'assign_group':
                    $result = $this->assignmentService->assignToGroup($ticket, $request->resolver_ids);
                    break;
                    
                case 'assign_myself':
                    $result = $this->assignmentService->assignToSelf($ticket, $user->id);
                    break;
                    
                case 'forward':
                    $result = $this->assignmentService->forwardTicket(
                        $ticket, 
                        $request->forward_to_department_id, 
                        $request->forward_notes
                    );
                    break;
                    
                default:
                    throw new \InvalidArgumentException('Invalid action');
            }

            // Update due date if provided
            if ($request->due_date) {
                $ticket->update(['due_date' => $request->due_date]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Bulk assign multiple tickets
     */
    public function bulkAssign(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'ticket_ids' => 'required|array|min:1',
            'ticket_ids.*' => 'exists:tickets,id',
            'action' => 'required|in:assign_individual,assign_group,assign_myself,forward',
            'resolver_id' => 'required_if:action,assign_individual,assign_myself|exists:users,id',
            'resolver_ids' => 'required_if:action,assign_group|array|min:2',
            'resolver_ids.*' => 'exists:users,id',
            'forward_to_department_id' => 'required_if:action,forward|exists:departments,id',
            'forward_notes' => 'required_if:action,forward|string',
            'due_date' => 'nullable|date|after_or_equal:today'
        ]);

        try {
            // Verify all tickets belong to user's department
            $tickets = Ticket::whereIn('id', $request->ticket_ids)
                ->where('assigned_department_id', $user->department_id)
                ->get();

            if ($tickets->count() !== count($request->ticket_ids)) {
                return response()->json(['error' => 'Some tickets do not belong to your department'], 403);
            }

            $assignmentData = [
                'action' => $request->action,
                'resolver_id' => $request->resolver_id,
                'resolver_ids' => $request->resolver_ids,
                'forward_to_department_id' => $request->forward_to_department_id,
                'forward_notes' => $request->forward_notes,
                'due_date' => $request->due_date,
                'admin_id' => $user->id
            ];

            $result = $this->assignmentService->bulkAssign($request->ticket_ids, $assignmentData);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update ticket ordering
     */
    public function updateTicketOrder(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'tickets' => 'required|array',
            'tickets.*.ticket_id' => 'required|exists:tickets,id',
            'tickets.*.sort_order' => 'required|integer|min:0'
        ]);

        try {
            // Verify all tickets belong to user's department
            $ticketIds = collect($request->tickets)->pluck('ticket_id');
            $count = Ticket::whereIn('id', $ticketIds)
                ->where('assigned_department_id', $user->department_id)
                ->count();

            if ($count !== $ticketIds->count()) {
                return response()->json(['error' => 'Some tickets do not belong to your department'], 403);
            }

            $result = $this->ticketService->updateTicketOrder($request->tickets);

            return response()->json(['success' => $result]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update ticket order'
            ], 500);
        }
    }

    /**
     * Get resolver details
     */
    public function getResolverDetails(Request $request, $resolverId)
    {
        $user = Auth::user();
        
        // Verify resolver belongs to same department
        $resolver = \App\Models\User::where('id', $resolverId)
            ->where('department_id', $user->department_id)
            ->where('is_resolver', true)
            ->first();

        if (!$resolver) {
            return response()->json(['error' => 'Resolver not found'], 404);
        }

        $details = $this->resolverService->getResolverDetails($resolverId);

        return response()->json($details);
    }

    /**
     * Update resolver status (activate/suspend)
     */
    public function updateResolverStatus(Request $request, $resolverId)
    {
        $user = Auth::user();
        
        $request->validate([
            'status' => 'required|in:activate,suspend'
        ]);

        try {
            // Verify resolver belongs to same department
            $resolver = \App\Models\User::where('id', $resolverId)
                ->where('department_id', $user->department_id)
                ->where('is_resolver', true)
                ->firstOrFail();

            if ($request->status === 'activate') {
                $result = $this->resolverService->activateResolver($resolverId);
            } else {
                $result = $this->resolverService->suspendResolver($resolverId);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Bulk update resolver status
     */
    public function bulkUpdateResolverStatus(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'resolver_ids' => 'required|array|min:1',
            'resolver_ids.*' => 'exists:users,id',
            'status' => 'required|in:activate,suspend'
        ]);

        try {
            // Verify all resolvers belong to same department
            $count = \App\Models\User::whereIn('id', $request->resolver_ids)
                ->where('department_id', $user->department_id)
                ->where('is_resolver', true)
                ->count();

            if ($count !== count($request->resolver_ids)) {
                return response()->json(['error' => 'Some resolvers do not belong to your department'], 403);
            }

            $result = $this->resolverService->bulkUpdateResolverStatus($request->resolver_ids, $request->status);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get chart data with specific time range
     */
    public function getChartData(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->department_id) {
            return response()->json(['error' => 'No department assigned'], 403);
        }

        $timeRange = $request->get('timeRange', '90d');
        $chartData = $this->ticketService->getDepartmentChartData($user->department_id, $timeRange);

        return response()->json($chartData);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->department_id) {
            return response()->json(['error' => 'No department assigned'], 403);
        }

        $statistics = $this->ticketService->getDepartmentStatistics($user->department_id);

        return response()->json($statistics);
    }
}
