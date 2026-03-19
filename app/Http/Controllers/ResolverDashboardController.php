<?php
// app/Http/Controllers/ResolverDashboardController.php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Department;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResolverDashboardController extends Controller
{
    /**
     * Display resolver dashboard
     */
    public function index(Request $request): Response
    {
        $resolver = $request->user();
        
        if (!$resolver->is_resolver) {
            abort(403, 'Resolver access required.');
        }

        $statistics = $this->getResolverStatisticsData($resolver);
        $recentTickets = $this->getResolverTicketsData($resolver, ['per_page' => 5]);
        $chartData = $this->getResolverChartDataPrivate($resolver);

        return Inertia::render('ResolverDashboard', [
            'statistics' => $statistics,
            'recentTickets' => $recentTickets['tickets'],
            'chartData' => $chartData,
            'user' => [
                'is_resolver' => true,
                'name' => $resolver->name,
                'department_id' => $resolver->department_id
            ]
        ]);
    }

    /**
     * Get resolver tickets for AJAX requests
     */
    public function getResolverTickets(Request $request)
    {
        $resolver = $request->user();
        
        if (!$resolver->is_resolver) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tickets = $this->getResolverTicketsData($resolver, $request->all());

        return response()->json($tickets);
    }

    /**
     * Get resolver statistics for AJAX requests
     */
    public function getResolverStatistics(Request $request)
    {
        $resolver = $request->user();
        
        if (!$resolver->is_resolver) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $statistics = $this->getResolverStatisticsData($resolver);

        return response()->json($statistics);
    }

    /**
     * Get resolver chart data for AJAX requests
     */
    public function getResolverChartData(Request $request)
    {
        $resolver = $request->user();
        
        if (!$resolver->is_resolver) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $timeRange = $request->get('timeRange', '90d');
        $chartData = $this->getResolverChartDataPrivate($resolver, $timeRange);

        return response()->json($chartData);
    }

    /**
     * Get group members for a ticket
     */
    public function getGroupMembers(Request $request, $ticketId)
    {
        $resolver = $request->user();
        
        if (!$resolver->is_resolver) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$resolver->department_id) {
            return response()->json(['error' => 'No department assigned'], 403);
        }

        $tableName = 'dept_' . $resolver->department->slug . '_tickets';
        
        // Verify the resolver has access to this ticket
        $hasAccess = DB::table($tableName)
            ->where('ticket_id', $ticketId)
            ->where(function($query) use ($resolver) {
                $query->where('assigned_resolver_id', $resolver->id)
                      ->orWhere('assignment_type', 'group'); // Group assignments are accessible to all group members
            })
            ->exists();

        if (!$hasAccess) {
            return response()->json(['error' => 'Ticket not found or access denied'], 404);
        }

        // Get group members for group assignments
        $ticketAssignment = DB::table($tableName)
            ->where('ticket_id', $ticketId)
            ->first();

        if ($ticketAssignment && $ticketAssignment->assignment_type === 'group') {
            // Get all resolvers assigned to this group
            $groupMembers = DB::table($tableName)
                ->where('assignment_group_id', $ticketAssignment->assignment_group_id)
                ->where('assignment_type', 'group')
                ->pluck('assigned_resolver_id')
                ->filter();

            $users = User::whereIn('id', $groupMembers)
                ->select('id', 'name', 'email', 'phone')
                ->get();

            return response()->json(['group_members' => $users]);
        }

        return response()->json(['group_members' => []]);
    }

    /**
     * Get resolver statistics
     */
    private function getResolverStatisticsData($resolver)
    {
        if (!$resolver->department_id) {
            return [
                'total_tickets_assigned' => 0,
                'total_resolved_tickets' => 0,
                'overdue_tickets' => 0,
                'resolver_groups' => 0,
                'assigned_tickets' => 0,
                'in_progress_tickets' => 0,
                'resolved_tickets' => 0,
                'group_tickets' => 0,
                'individual_tickets' => 0
            ];
        }

        $tableName = 'dept_' . $resolver->department->slug . '_tickets';

        $stats = [
            'total_tickets_assigned' => DB::table($tableName)
                ->where(function($query) use ($resolver) {
                    $query->where('assigned_resolver_id', $resolver->id)
                          ->orWhere('assignment_type', 'group');
                })
                ->count(),
            
            'assigned_tickets' => DB::table($tableName)
                ->join('tickets', 'tickets.id', '=', $tableName . '.ticket_id')
                ->where(function($query) use ($resolver) {
                    $query->where($tableName . '.assigned_resolver_id', $resolver->id)
                          ->orWhere($tableName . '.assignment_type', 'group');
                })
                ->where('tickets.status', 'assigned')
                ->count(),
            
            'in_progress_tickets' => DB::table($tableName)
                ->join('tickets', 'tickets.id', '=', $tableName . '.ticket_id')
                ->where(function($query) use ($resolver) {
                    $query->where($tableName . '.assigned_resolver_id', $resolver->id)
                          ->orWhere($tableName . '.assignment_type', 'group');
                })
                ->where('tickets.status', 'in_progress')
                ->count(),
            
            'resolved_tickets' => DB::table($tableName)
                ->join('tickets', 'tickets.id', '=', $tableName . '.ticket_id')
                ->where(function($query) use ($resolver) {
                    $query->where($tableName . '.assigned_resolver_id', $resolver->id)
                          ->orWhere($tableName . '.assignment_type', 'group');
                })
                ->where('tickets.status', 'resolved')
                ->count(),
            
            'overdue_tickets' => DB::table($tableName)
                ->join('tickets', 'tickets.id', '=', $tableName . '.ticket_id')
                ->where(function($query) use ($resolver) {
                    $query->where($tableName . '.assigned_resolver_id', $resolver->id)
                          ->orWhere($tableName . '.assignment_type', 'group');
                })
                ->where($tableName . '.due_date', '<', now())
                ->whereNotIn('tickets.status', ['resolved', 'closed'])
                ->count(),
            
            'group_tickets' => DB::table($tableName)
                ->where('assignment_type', 'group')
                ->count(),
            
            'individual_tickets' => DB::table($tableName)
                ->where('assigned_resolver_id', $resolver->id)
                ->where('assignment_type', 'individual')
                ->count(),
        ];

        $stats['total_resolved_tickets'] = $stats['resolved_tickets'];
        $stats['resolver_groups'] = $stats['group_tickets'];

        return $stats;
    }

    /**
     * Get resolver tickets
     */
    private function getResolverTicketsData($resolver, $options = [])
    {
        if (!$resolver->department_id) {
            return ['tickets' => [], 'total' => 0];
        }

        $tableName = 'dept_' . $resolver->department->slug . '_tickets';
        $perPage = $options['per_page'] ?? 20;
        $page = $options['page'] ?? 1;

        $query = DB::table($tableName)
            ->join('tickets', 'tickets.id', '=', $tableName . '.ticket_id')
            ->select(
                $tableName . '.*',
                'tickets.subject',
                'tickets.description',
                'tickets.category',
                'tickets.priority',
                'tickets.status',
                'tickets.created_at',
                'tickets.requester_name',
                'tickets.requester_email'
            )
            ->where(function($query) use ($resolver) {
                $query->where($tableName . '.assigned_resolver_id', $resolver->id)
                      ->orWhere($tableName . '.assignment_type', 'group');
            })
            ->orderBy($tableName . '.created_at', 'desc');

        // Apply filters
        if (isset($options['status']) && $options['status']) {
            $query->where('tickets.status', $options['status']);
        }

        if (isset($options['priority']) && $options['priority']) {
            $query->where('tickets.priority', $options['priority']);
        }

        if (isset($options['search']) && $options['search']) {
            $query->where(function($q) use ($options) {
                $q->where('tickets.ticket_number', 'like', '%' . $options['search'] . '%')
                  ->orWhere('tickets.subject', 'like', '%' . $options['search'] . '%');
            });
        }

        $total = $query->count();
        $tickets = $query->offset(($page - 1) * $perPage)
                        ->limit($perPage)
                        ->get()
                        ->map(function($ticket) {
                            return [
                                'id' => $ticket->ticket_id,
                                'ticket_number' => $ticket->ticket_number,
                                'subject' => $ticket->subject,
                                'description' => $ticket->description,
                                'category' => $ticket->category,
                                'priority' => $ticket->priority,
                                'status' => $ticket->status,
                                'assignment_type' => $ticket->assignment_type,
                                'assigned_resolver_id' => $ticket->assigned_resolver_id,
                                'assignment_group_id' => $ticket->assignment_group_id,
                                'due_date' => $ticket->due_date,
                                'created_at' => $ticket->created_at,
                                'requester_name' => $ticket->requester_name,
                                'requester_email' => $ticket->requester_email,
                            ];
                        });

        return [
            'tickets' => $tickets,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page
        ];
    }

    /**
     * Get resolver chart data
     */
    private function getResolverChartDataPrivate($resolver, $timeRange = '90d')
    {
        if (!$resolver->department_id) {
            return [];
        }

        $tableName = 'dept_' . $resolver->department->slug . '_tickets';
        
        // Calculate date range
        $days = $timeRange === '30d' ? 30 : ($timeRange === '7d' ? 7 : 90);
        $startDate = now()->subDays($days)->startOfDay();

        $data = DB::table($tableName)
            ->join('tickets', 'tickets.id', '=', $tableName . '.ticket_id')
            ->select(
                DB::raw('DATE(tickets.created_at) as date'),
                DB::raw('COUNT(*) as tickets_created'),
                DB::raw('SUM(CASE WHEN tickets.status = "resolved" THEN 1 ELSE 0 END) as tickets_resolved')
            )
            ->where($tableName . '.assigned_resolver_id', $resolver->id)
            ->where('tickets.created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(tickets.created_at)'))
            ->orderBy('date')
            ->get();

        return $data->map(function($item) {
            return [
                'date' => $item->date,
                'tickets_created' => $item->tickets_created,
                'tickets_resolved' => $item->tickets_resolved,
            ];
        });
    }
}