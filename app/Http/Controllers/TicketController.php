<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Resolver;
use App\Models\Department;
use App\Models\TicketAssignment;
use App\Models\TicketHistory;
use App\Models\TicketRouting;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    protected $departmentRoutingService;

    public function __construct()
    {
        $this->departmentRoutingService = new DepartmentRoutingService();
    }

    // EXISTING FRONTEND METHODS (KEEP UNCHANGED)
    public function create()
    {
        // Get user's tickets to display alongside the form
        $tickets = Ticket::where('requester_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
         // Get active departments for the recipient dropdown
    $departments = Department::where('is_active', true)->get(['id', 'name', 'description']);

        return Inertia::render('create', [
            'tickets' => $tickets,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    { 
          \Log::debug('=== TICKET STORE START ===');
    \Log::debug('Request data:', $request->all());
        // Get the authenticated user
        $user = Auth::user();
        
         if (!$user) {
        \Log::error('No authenticated user found!');
        return redirect()->back()
            ->with('error', 'You must be logged in to create a ticket.');
    }

    \Log::debug('User department info:', [
        'department_id' => $user->department_id,
    ]);

        // Check for duplicate ticket (same user + same subject)
        $duplicate = Ticket::where('requester_id', $user->id)
            ->where('subject', $request->subject)
            ->first();

        if ($duplicate) {
            return redirect()->back()
                ->with('error', 'A ticket with this subject already exists. Please use a different subject.');
        }

        // Validate the request data
         try {$validated = $request->validate([
            'requester_type' => 'required|string|max:255',
            'brunch' => 'required|string|max:255',
            'recipant' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'priority' => 'required|string|max:255',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);     
        \Log::debug('Validation passed:', $validated);
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Validation failed:', $e->errors());
        throw $e;
    }

        try {
            DB::beginTransaction();
             \Log::debug('Transaction started');
            // Handle file upload if present
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
            \Log::debug('File uploaded to:', [$attachmentPath]);
        }
        $userDepartment = 'Unknown Department';
        if ($user->department_id) {
            $department = Department::find($user->department_id);
            $userDepartment = $department ? $department->name : 'Unknown Department';
        }
        \Log::debug('User department determined:', [$userDepartment]);


            // Create the ticket with auto-generated user info
            $ticket = Ticket::create([
                'requester_id' => $user->id,   
                'requester_name' => $user->name,
                'requester_email' => $user->email,
                'requester_type' => $validated['requester_type'],
                'department' => $userDepartment,
                'brunch' => $validated['brunch'],
                'recipant' => $validated['recipant'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'attachment' => $validated['attachment'] ?? null,
                'status' => 'open'
            ]);
                  // NEW: Route ticket to appropriate department using new routing system
            $targetDepartment = Department::where('name', 'like', '%' . $validated['recipant'] . '%')
                                        ->orWhere('slug', $validated['recipant'])
                                        ->first();
            
            if ($targetDepartment) {
                // Create routing record
                TicketRouting::routeTicket($ticket, $targetDepartment, $user);
                
                // Add to department ticket table
                $tableName = 'dept_' . $targetDepartment->slug . '_tickets';
                DB::table($tableName)->insert([
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Update main ticket with assigned department
                $ticket->assigned_department_id = $targetDepartment->id;
                $ticket->save();
                
                \Log::debug('Ticket routed to department:', [
                    'department_id' => $targetDepartment->id,
                    'department_name' => $targetDepartment->name
                ]);
            } else {
                \Log::warning('Could not route ticket to department based on recipant:', [
                    'recipant' => $ticket->recipant
                ]);
            }
           
            // NEW: Log the creation
            TicketHistory::log(
                $ticket->id,
                null, // No resolver for user-created tickets
                'created',
                "Ticket created by user and auto-assigned to department",
                [
                    'assigned_department_id' => $ticket->assigned_department_id,
                    'assigned_department' => $ticket->assignedDepartment->name ?? 'Unknown',
                    'user_department' => $userDepartment,
                'user_selected_recipient' => $validated['recipant']
                ]
            );

            DB::commit();
                 \Log::debug('=== TICKET STORE SUCCESS ===');
            return redirect()->route('tickets.create')->with('default', 'Ticket created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
             \Log::error('Ticket creation failed:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()
            ->with('error', 'Failed to create ticket: ' . $e->getMessage());
        }
        
    }

    public function update(Request $request, Ticket $ticket)
    {
        // Authorization - users can only update their own tickets
        if ($ticket->requester_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check for duplicate (excluding current ticket)
        if ($request->subject !== $ticket->subject) {
            $duplicate = Ticket::where('requester_id', Auth::id())
                ->where('subject', $request->subject)
                ->where('id', '!=', $ticket->id)
                ->first();

            if ($duplicate) {
                return redirect()->back()
                    ->with('error', 'A ticket with this subject already exists.');
            }
        }

        // Validate
        $validated = $request->validate([
            'requester_type' => 'required|string|max:255',
            'brunch' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'recipant' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'priority' => 'required|string|max:255',
            'attachment' => 'nullable|file|max:10240',
        ]);

        try {
            DB::beginTransaction();

            // Update ticket
            $ticket->update($validated);

            // Handle new attachment
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($ticket->attachment) {
                    Storage::delete($ticket->attachment);
                }
                $ticket->attachment = $request->file('attachment')->store('attachments');
                $ticket->save();
            }

            // NEW: Log the update
            TicketHistory::log(
                $ticket->id,
                Auth::id(),
                'updated',
                "Ticket updated by user",
                ['changes' => $validated]
            );

            DB::commit();

            return redirect()->back()->with('success', 'Ticket updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update ticket: ' . $e->getMessage());
        }
    }

    public function destroy(Ticket $ticket)
    {
        // Authorization - users can only delete their own tickets
        if ($ticket->requester_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            // Delete attachment if exists
            if ($ticket->attachment) {
                Storage::delete($ticket->attachment);
            }

            // NEW: Log the deletion before actually deleting
            TicketHistory::log(
                $ticket->id,
                Auth::id(),
                'deleted',
                "Ticket deleted by user",
                ['ticket_data' => $ticket->toArray()]
            );

            $ticket->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Ticket deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to delete ticket: ' . $e->getMessage());
        }
    }

    public function show(Ticket $ticket)
    {
        // Authorization - users can only view their own tickets
        if ($ticket->requester_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // NEW: Load relationships for better display
        $ticket->load(['assignedDepartment', 'assignedResolver', 'histories.resolver']);

        return Inertia::render('TicketShow', [
            'ticket' => $ticket
        ]);
    }

    // NEW API METHODS FOR ADMIN/RESOLVER FUNCTIONALITY
    // These methods will be used by the admin interface

    /**
     * API: List tickets for resolver's department (for admin interface)
     */
    public function indexApi(Request $request)
    {
        // Get the authenticated resolver
        $resolver = $request->user();
        
        // Get query parameters
        $status = $request->get('status');
        $priority = $request->get('priority');
        $category = $request->get('category');
        $groupBy = $request->get('groupby', 'category');
        
        // Start query for resolver's department
        $query = Ticket::with(['assignedDepartment', 'assignedResolver', 'histories'])
            ->where('assigned_department_id', $resolver->department_id);
        
        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($priority) {
            $query->where('priority', $priority);
        }
        
        if ($category) {
            $query->where('category', 'like', "%{$category}%");
        }
        
        // Apply grouping
        if (in_array($groupBy, ['category', 'priority', 'status'])) {
            $query->orderBy($groupBy);
        }
        
        $tickets = $query->latest()->paginate(20);
        
        return response()->json([
            'tickets' => $tickets,
            'filters' => [
                'status' => $status,
                'priority' => $priority,
                'category' => $category,
                'groupBy' => $groupBy
            ]
        ]);
    }

    /**
     * API: Assign tickets to resolvers (bulk assignment for admin interface)
     */
    public function assignApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'exists:tickets,id',
            'resolver_id' => 'required|exists:resolvers,id',
            'due_date' => 'required|date|after:now',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $resolver = Resolver::find($request->resolver_id);
        $assigner = $request->user();

        // Verify the resolver belongs to the assigner's department
        if ($resolver->department_id !== $assigner->department_id) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Cannot assign to resolver outside your department'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $assignedTickets = [];

            foreach ($request->ticket_ids as $ticketId) {
                $ticket = Ticket::find($ticketId);
                
                // Verify ticket belongs to assigner's department
                if ($ticket->assigned_department_id !== $assigner->department_id) {
                    continue; // Skip unauthorized tickets
                }

                // Update ticket assignment
                $ticket->assigned_resolver_id = $resolver->id;
                $ticket->due_date = $request->due_date;
                $ticket->status = 'assigned';
                $ticket->save();

                // Create assignment record
                $assignment = TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'assigned_by' => $assigner->id,
                    'assigned_to' => $resolver->id,
                    'notes' => $request->notes,
                    'due_date' => $request->due_date
                ]);

                // Log the assignment
                TicketHistory::log(
                    $ticket->id,
                    $assigner->id,
                    'assigned',
                    "Ticket assigned to {$resolver->name}",
                    [
                        'assigned_to' => $resolver->name,
                        'due_date' => $request->due_date,
                        'notes' => $request->notes
                    ]
                );

                $assignedTickets[] = $ticket->load('assignedResolver');
            }

            DB::commit();

            return response()->json([
                'message' => count($assignedTickets) . ' tickets assigned successfully',
                'assigned_tickets' => $assignedTickets
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to assign tickets',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Get department statistics (for admin dashboard)
     */
    public function statisticsApi(Request $request)
    {
        $departmentId = $request->user()->department_id;
        
        $stats = [
            'total' => Ticket::where('assigned_department_id', $departmentId)->count(),
            'open' => Ticket::where('assigned_department_id', $departmentId)
                        ->where('status', 'open')->count(),
            'assigned' => Ticket::where('assigned_department_id', $departmentId)
                          ->where('status', 'assigned')->count(),
            'in_progress' => Ticket::where('assigned_department_id', $departmentId)
                             ->where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('assigned_department_id', $departmentId)
                          ->where('status', 'resolved')->count(),
            'overdue' => Ticket::where('assigned_department_id', $departmentId)
                         ->where('due_date', '<', now())
                         ->whereNotIn('status', ['resolved', 'closed'])
                         ->count()
        ];

        return response()->json([
            'statistics' => $stats,
            'department' => Department::find($departmentId)
        ]);
    }
   


/**
     * Get resolvers for a department (for AJAX requests)
     */
    public function getDepartmentResolvers(Request $request, $departmentId)
    {
        $user = Auth::user();

        // Verify user has access to this department
        if (!$user->is_admin && $user->department_id != $departmentId) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You can only access your own department'
            ], 403);
        }

        $resolvers = User::where('department_id', $departmentId)
            ->where('is_resolver', true)
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'department_id', 'is_active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'resolvers' => $resolvers
        ]);
    }

    /**
     * Get all active departments (for forwarding)
     */
    public function getAllDepartments(Request $request)
    {
        $user = Auth::user();

        // Only admins can see all departments
        if (!$user->is_admin) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only administrators can access this resource'
            ], 403);
        }

        $departments = Department::where('is_active', true)
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        return response()->json([
            'departments' => $departments
        ]);
    }

    /**
     * Assign ticket to resolver(s)
     */
    public function assignTicket(Request $request, $ticketId)
    {
        $user = Auth::user();
        $ticket = Ticket::findOrFail($ticketId);

        // Verify user has access to this ticket
        if ($ticket->assigned_department_id != $user->department_id) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Ticket does not belong to your department'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'due_date' => 'nullable|date|after_or_equal:today',
            'action' => 'required|in:assign_myself,assign_individual,assign_group,update_due_date,forward',
            'resolver_id' => 'required_if:action,assign_myself,assign_individual|integer|exists:users,id',
            'resolver_ids' => 'required_if:action,assign_group|array|min:2',
            'resolver_ids.*' => 'integer|exists:users,id',
            'forward_to_department_id' => 'required_if:action,forward|integer|exists:departments,id',
            'forward_notes' => 'required_if:action,forward|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update due date if provided
            if ($request->due_date) {
                $ticket->due_date = $request->due_date;
            }

            $action = $request->action;
            $assignmentType = null;
            $resolverIds = [];
            $groupId = null;

            switch ($action) {
                case 'assign_myself':
                    // Assign to current user
                    $assignmentType = 'self';
                    $resolverIds = [$user->id];

                    // Update department table
                    $ticket->assignInDepartment(
                        $user->department, 
                        'self', 
                        null, null, $user->id, 
                        $request->due_date
                    );

                    // Update main ticket
                    $ticket->assigned_resolver_id = $user->id;
                    $ticket->status = 'in_progress';
                    $ticket->assignment_type = 'individual';
                    $ticket->save();

                    break;

                case 'assign_individual':
                    // Assign to specific resolver
                    $resolver = User::findOrFail($request->resolver_id);

                    // Verify resolver belongs to same department
                    if ($resolver->department_id != $user->department_id) {
                        throw new \Exception('Resolver must be from the same department');
                    }

                    $assignmentType = 'individual';
                    $resolverIds = [$resolver->id];

                    // Update department table
                    $ticket->assignInDepartment(
                        $user->department, 
                        'individual', 
                        $resolver->id, null, $user->id, 
                        $request->due_date
                    );

                    // Update main ticket
                    $ticket->assigned_resolver_id = $resolver->id;
                    $ticket->status = 'assigned';
                    $ticket->assignment_type = 'individual';
                    $ticket->save();

                    break;

                case 'assign_group':
                    // Generate unique group ID
                    $groupId = 'GROUP-' . strtoupper(Str::random(8)) . '-' . time();
                    $resolverIds = $request->resolver_ids;

                    // Verify all resolvers belong to same department
                    $count = User::whereIn('id', $resolverIds)
                        ->where('department_id', $user->department_id)
                        ->count();

                    if ($count != count($resolverIds)) {
                        throw new \Exception('All resolvers must be from the same department');
                    }

                    $assignmentType = 'group';

                    // Update department table
                    $ticket->assignInDepartment(
                        $user->department, 
                        'group', 
                        null, $groupId, $user->id, 
                        $request->due_date
                    );

                    // Update main ticket
                    $ticket->assigned_resolver_id = null; // No primary resolver for group
                    $ticket->status = 'assigned';
                    $ticket->assignment_type = 'group';
                    $ticket->group_id = $groupId;
                    $ticket->save();

                    break;

                case 'forward':
                    // Forward to another department
                    $targetDepartment = Department::findOrFail($request->forward_to_department_id);
                    $originalDepartment = Department::find($ticket->assigned_department_id);

                    // Store in original department table for history (optional)
                    $sourceTableName = 'dept_' . ($originalDepartment ? $originalDepartment->slug : 'unknown') . '_tickets';

                    // Update ticket
                    $ticket->assigned_department_id = $targetDepartment->id;
                    $ticket->assigned_resolver_id = null;
                    $ticket->status = 'forwarded';
                    $ticket->assignment_type = 'individual';

                    // Append forward note to description
                    $forwardHeader = "\n\n--- 🔄 FORWARDED FROM " . strtoupper($originalDepartment?->name ?? 'Unknown') . " ---\n";
                    $forwardHeader .= "Date: " . now()->format('Y-m-d H:i:s') . "\n";
                    $forwardHeader .= "Forwarded by: " . $user->name . "\n";
                    $forwardHeader .= "Note: " . $request->forward_notes . "\n";
                    $forwardHeader .= "--- END OF FORWARD NOTE ---\n";

                    $ticket->description = $ticket->description . $forwardHeader;

                    // Add to target department table
                    $targetTableName = 'dept_' . $targetDepartment->slug . '_tickets';

                    // Check if table exists and insert
                    if (\Illuminate\Support\Facades\Schema::hasTable($targetTableName)) {
                        DB::table($targetTableName)->insert([
                            'ticket_id' => $ticket->id,
                            'ticket_number' => $ticket->ticket_number,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $resolverIds = [];
                    $assignmentType = 'forwarded';

                    break;

                case 'update_due_date':
                    // Just update due date, keep existing assignment
                    $ticket->save();

                    TicketHistory::log(
                        $ticket->id,
                        $user->id,
                        'due_date_updated',
                        "Due date updated to " . ($ticket->due_date ? $ticket->due_date->format('Y-m-d') : 'none')
                    );

                    DB::commit();

                    return response()->json([
                        'message' => 'Due date updated successfully',
                        'ticket' => $ticket->fresh(['assignedDepartment', 'assignedResolver'])
                    ]);
            }

            $ticket->save();

            // Log the assignment (skip for forwarded case as we'll log separately)
            if ($action !== 'forward') {
                $resolverNames = User::whereIn('id', $resolverIds)->pluck('name')->implode(', ');

                TicketHistory::log(
                    $ticket->id,
                    $user->id,
                    $assignmentType === 'group' ? 'assigned_group' : 'assigned',
                    $assignmentType === 'group' 
                        ? "Ticket assigned to group ({$resolverNames})" 
                        : "Ticket assigned to {$resolverNames}",
                    [
                        'assignment_type' => $assignmentType,
                        'resolver_ids' => $resolverIds,
                        'group_id' => $groupId,
                        'due_date' => $ticket->due_date?->format('Y-m-d')
                    ]
                );
            } else {
                // Log forwarding
                TicketHistory::log(
                    $ticket->id,
                    $user->id,
                    'forwarded',
                    "Ticket forwarded to " . ($targetDepartment->name ?? 'another department'),
                    [
                        'from_department_id' => $originalDepartment?->id,
                        'to_department_id' => $targetDepartment->id,
                        'notes' => $request->forward_notes
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'message' => $action === 'forward' ? 'Ticket forwarded successfully' : 'Ticket assigned successfully',
                'ticket' => $ticket->fresh(['assignedDepartment', 'assignedResolver']),
                'group_id' => $groupId ?? null
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Operation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get resolvers for the current department (for the resolvers tab)
     */
    public function getDepartmentResolversList(Request $request)
    {
        $user = Auth::user();

        if (!$user->department_id) {
            return response()->json([
                'resolvers' => []
            ]);
        }

        $resolvers = User::where('department_id', $user->department_id)
            ->where('is_resolver', true)
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'department_id', 'is_active', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(function($resolver) {
                return [
                    'id' => $resolver->id,
                    'name' => $resolver->name,
                    'email' => $resolver->email,
                    'department_id' => $resolver->department_id,
                    'is_active' => $resolver->is_active,
                    'joined_at' => $resolver->created_at->format('Y-m-d'),
                    'tickets_resolved' => $this->getResolverTicketCount($resolver->id, 'resolved'),
                    'tickets_assigned' => $this->getResolverTicketCount($resolver->id, 'assigned'),
                    'tickets_in_progress' => $this->getResolverTicketCount($resolver->id, 'in_progress')
                ];
            });

        return response()->json([
            'resolvers' => $resolvers
        ]);
    }

    /**
     * Helper to get resolver ticket count by status
     */
    private function getResolverTicketCount($resolverId, $status)
    {
        return ResolverTicket::where('resolver_id', $resolverId)
            ->where('status', $status)
            ->count();
    }
}