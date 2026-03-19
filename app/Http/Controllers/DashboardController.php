<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Department;
use App\Services\TicketService;
use App\Services\ResolverService;
use App\Services\DepartmentDataService;
use DB;
use Hash;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $ticketService;
    protected $resolverService;
    protected $departmentDataService;

    public function __construct(
        TicketService $ticketService,
        ResolverService $resolverService,
        DepartmentDataService $departmentDataService
    ) {
        $this->ticketService = $ticketService;
        $this->resolverService = $resolverService;
        $this->departmentDataService = $departmentDataService;
    }

    /**
     * Display the dashboard page.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Get active departments and convert to array
        $departments = Department::active()->get()->toArray();
        
        // Add department list to the frontend
        $departments = Department::all(['id', 'name', 'slug']);

        // Prepare dashboard data based on user role and department
        $dashboardData = $this->getDashboardData($user);
        
        return Inertia::render('dashboard', [
            'user_has_department' => !is_null($user->department_id),
            'user_is_admin' => $user->is_admin,
            'user_is_resolver' => $user->is_resolver,
            'user_is_none' => $user->is_none,
            'user_branch' => $user->branch,
            'departments' => $departments,
            'branches' => ['Main Branch', 'North Branch', 'South Branch', 'East Branch', 'West Branch'],
            'dashboardData' => $dashboardData,
            'resolverData' => $dashboardData['resolverData'] ?? null
        ]);
    }

    /**
     * Get dashboard data based on user role and department
     */
    private function getDashboardData($user)
    {
        $statistics = [
            'total_tickets_to_resolve' => 0,
            'assigned_tickets' => 0,
            'resolved_tickets' => 0,
            'overdue_tickets' => 0,
            'active_resolvers' => 0,
            'assigned_resolver_groups' => 0
        ];

        $chartData = [];
        $recentTickets = [];

        // Only fetch department-specific data if user has a department
        if ($user->department_id) {
            $department = Department::find($user->department_id);
            
            if ($department) {
                // Get department statistics using new service
                $statistics = $this->ticketService->getDepartmentStatistics($department->id);
                
                // Get recent tickets
                $recentTicketsResult = $this->ticketService->getDepartmentTickets($department->id, [
                    'per_page' => 5,
                    'sort_by' => 'created_at',
                    'sort_direction' => 'desc'
                ]);
                     
                // Convert to array if it's a paginator object
                $recentTickets = $recentTicketsResult instanceof \Illuminate\Pagination\LengthAwarePaginator 
                    ? $recentTicketsResult->items() 
                    : $recentTicketsResult;

                // Get chart data
                $chartData = $this->ticketService->getDepartmentChartData($department->id, '90d');
            }
        } elseif ($user->is_admin) {
            // System admin gets system-wide overview
            $statistics = $this->getSystemWideStatistics();
            $chartData = $this->getSystemWideChartData();
            $recentTickets = $this->getSystemWideRecentTickets();
        } elseif ($user->is_resolver) {
            // Resolver gets their personal statistics
            $resolverStatistics = $this->ticketService->getResolverStatistics($user->id);
            $resolverChartData = []; // Can be implemented later
            $resolverTickets = $this->ticketService->getResolverTickets($user->id, [
                'per_page' => 5,
                'sort_by' => 'created_at',
                'sort_direction' => 'desc'
            ]);
            
            // Convert to array if it's a paginator object
            $resolverTickets = $resolverTickets instanceof \Illuminate\Pagination\LengthAwarePaginator 
                ? $resolverTickets->items() 
                : $resolverTickets;
                
            // Pass resolver data separately
            return [
                'statistics' => $statistics,
                'chartData' => $chartData,
                'recentTickets' => $recentTickets,
                'resolverData' => [
                    'statistics' => $resolverStatistics,
                    'chartData' => $resolverChartData,
                    'tickets' => $resolverTickets
                ]
            ];
        }

        return [
            'statistics' => $statistics,
            'chartData' => $chartData,
            'recentTickets' => is_object($recentTickets) && method_exists($recentTickets, 'items') 
                ? $recentTickets->items() 
                : (is_array($recentTickets) ? $recentTickets : [])
        ];
    }

    /**
     * Get system-wide statistics for admin users
     */
    private function getSystemWideStatistics()
    {
        return [
            'total_tickets' => Ticket::count(),
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'assigned_tickets' => Ticket::where('status', 'assigned')->count(),
            'resolved_tickets' => Ticket::where('status', 'resolved')->count(),
            'overdue_tickets' => Ticket::where('due_date', '<', now())
                                ->whereNotIn('status', ['resolved', 'closed'])
                                ->count(),
            'active_resolvers' => DB::table('users')
                                ->where('is_resolver', true)
                                ->where('is_active', true)
                                ->count()
        ];
    }

    /**
     * Get system-wide chart data for admin users
     */
    private function getSystemWideChartData()
    {
        $endDate = now();
        $startDate = clone $endDate;
        $startDate->subDays(90);
        
        $ticketsByDate = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $chartData = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $count = $ticketsByDate->firstWhere('date', $dateStr)->count ?? 0;
            
            $chartData[] = [
                'date' => $dateStr,
                'tickets' => $count
            ];
            
            $currentDate->addDay();
        }
        
        return $chartData;
    }

    /**
     * Get system-wide recent tickets for admin users
     */
    private function getSystemWideRecentTickets()
    {
         return Ticket::with(['assignedDepartment', 'assignedResolver'])
                ->orderBy('created_at', 'desc')
                ->take(5) // Use take() instead of paginate() to get array
                ->get()
                ->toArray();
    }

    public function registerDepartment(Request $request)
    {
        $user = Auth::user();
        
        // Validate the request
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'branch' => 'required|string|max:255',
            'is_admin' => 'boolean',
            'is_resolver' => 'boolean',
            'is_none' => 'boolean',
            'password' => 'required|current_password',
        ]);

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password. Please check your password and try again.'
            ], 401);
        }

        // Validate role selection
        if ($request->is_none && ($request->is_admin || $request->is_resolver)) {
            return response()->json([
                'message' => 'Cannot select "None" with Admin or Resolver roles.'
            ], 422);
        }

        if (!$request->is_none && !$request->is_admin && !$request->is_resolver) {
            return response()->json([
                'message' => 'Please select at least one role or choose "None".'
            ], 422);
        }

        try {
            // Update user details
            $user->department_id = $request->is_none ? null : $request->department_id;
            $user->branch = $request->branch;
            $user->is_admin = $request->is_admin ?? false;
            $user->is_resolver = $request->is_resolver ?? false;
            $user->is_none = $request->is_none ?? false;
            
            $user->save();

            return response()->json([
                'message' => 'Registration completed successfully!',
                'redirect' => '/dashboard'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating your profile. Please try again.'
            ], 500);
        }
    }

    /**
     * Get dashboard data for API-like requests (for components that need fresh data)
     */
    public function getDashboardDataJson(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        $dashboardData = $this->getDashboardData($user);
        
        return response()->json([
            'statistics' => $dashboardData['statistics'],
            'chartData' => $dashboardData['chartData'],
            'recentTickets' => $dashboardData['recentTickets']
        ]);
    }

    /**
     * Get filtered tickets for data table
     */
    public function getFilteredTickets(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        $filters = $request->all();

        // Handle different user types
        if ($user->department_id && ($user->is_admin || $user->is_resolver)) {
            // Department admin or resolver
            $tickets = $this->ticketService->getDepartmentTickets($user->department_id, $filters);
        } elseif ($user->is_resolver && !$user->department_id) {
            // Resolver without department (should not happen in normal flow)
            $tickets = $this->ticketService->getResolverTickets($user->id, $filters);
        } elseif ($user->is_admin && !$user->department_id) {
            // System admin - get all tickets
            $tickets = $this->getSystemWideTickets($filters);
        } else {
            return response()->json([
                'error' => 'Access denied or no department assigned'
            ], 403);
        }
        
        return response()->json([
            'tickets' => $tickets,
            'filters' => $filters
        ]);
    }

    /**
     * Get chart data with specific time range
     */
    public function getChartData(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        $timeRange = $request->get('timeRange', '90d');

        // Handle different user types
        if ($user->department_id && ($user->is_admin || $user->is_resolver)) {
            // Department admin or resolver
            $chartData = $this->ticketService->getDepartmentChartData($user->department_id, $timeRange);
        } elseif ($user->is_admin && !$user->department_id) {
            // System admin
            $chartData = $this->getSystemWideChartDataByTimeRange($timeRange);
        } else {
            return response()->json([
                'error' => 'Access denied or no department assigned'
            ], 403);
        }
        
        return response()->json($chartData);
    }

    /**
     * Get system-wide tickets for admin
     */
    private function getSystemWideTickets(array $filters = [])
    {
        $query = Ticket::with(['assignedDepartment', 'assignedResolver']);

        // Apply filters
        $this->applySystemFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Apply filters for system-wide tickets
     */
    private function applySystemFilters($query, array $filters): void
    {
        // Status filter
        if (!empty($filters['status']) && $filters['status'] !== 'All_value') {
            $query->where('status', $filters['status']);
        }

        // Priority filter
        if (!empty($filters['priority']) && $filters['priority'] !== 'All_priority') {
            $query->where('priority', $filters['priority']);
        }

        // Category filter
        if (!empty($filters['category']) && $filters['category'] !== 'All_catagories') {
            $query->where('category', $filters['category']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('ticket_number', 'like', '%' . $searchTerm . '%')
                  ->orWhere('subject', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Department filter
        if (!empty($filters['department_id'])) {
            $query->where('assigned_department_id', $filters['department_id']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSortColumns = ['created_at', 'due_date', 'priority', 'status', 'ticket_number', 'subject'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }

    /**
     * Get system-wide chart data by time range
     */
    private function getSystemWideChartDataByTimeRange(string $timeRange = '90d')
    {
        $endDate = now();
        $startDate = clone $endDate;
        
        switch ($timeRange) {
            case '30d':
                $startDate->subDays(30);
                break;
            case '7d':
                $startDate->subDays(7);
                break;
            default: // 90d
                $startDate->subDays(90);
                break;
        }
        
        $ticketsByDate = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $chartData = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $count = $ticketsByDate->firstWhere('date', $dateStr)->count ?? 0;
            
            $chartData[] = [
                'date' => $dateStr,
                'tickets' => $count
            ];
            
            $currentDate->addDay();
        }
        
        return $chartData;
    }
}
