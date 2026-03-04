<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\Resolver;
use App\Models\TicketAssignment;
use App\Models\TicketHistory;
use App\Services\DepartmentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    protected $departmentDataService;

    public function __construct(DepartmentDataService $departmentDataService)
    {
        $this->departmentDataService = $departmentDataService;
    }

    /**
     * Department dashboard - render with data
     */
    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->department_id) {
            abort(403, 'You are not assigned to any department');
        }

        $department = Department::find($user->department_id);
        
        if (!$department) {
            abort(404, 'Department not found');
        }

        // Get department data using the service
        $departmentData = $this->departmentDataService->getDepartmentOverview($department->slug);

        return Inertia::render('DepartmentDashboard', [
            'department' => $department,
            'statistics' => $departmentData['statistics'],
            'recentTickets' => $departmentData['recent_tickets']->items(),
            'chartData' => $departmentData['chart_data'],
            'user' => [
                'is_admin' => $user->is_admin,
                'is_resolver' => $user->is_resolver
            ]
        ]);
    }

    /**
     * Get all departments (for admin only) - RENDER WITH DATA
     */
    public function index(Request $request): Response
    {
        // Only admins can see all departments
        if (!$request->user()->is_admin) {
            abort(403, 'Only administrators can access this resource');
        }

        $departments = Department::withCount(['resolvers', 'tickets'])->get();
        
        // Get statistics for each department
        $departmentsWithStats = $departments->map(function ($department) {
            $stats = $this->departmentDataService->getDepartmentStatistics($department->slug);
            return [
                'id' => $department->id,
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
                'is_active' => $department->is_active,
                'resolvers_count' => $department->resolvers_count,
                'tickets_count' => $department->tickets_count,
                'statistics' => $stats
            ];
        });

        return Inertia::render('Admin/Departments', [
            'departments' => $departmentsWithStats
        ]);
    }

    /**
     * Get department details with statistics - RENDER WITH DATA
     */
    public function show(Request $request, $id): Response
    {
        $user = $request->user();
        
        // Department admins can only see their own department
        if ($user->is_admin && $user->department_id == $id) {
            // Allow department admin to see their own department
        } elseif (!$user->is_admin) {
            // Resolvers can only see their own department
            if ($user->department_id != $id) {
                abort(403, 'You can only access your own department');
            }
        }

        $department = Department::withCount(['resolvers', 'tickets'])->findOrFail($id);

        // Get department statistics using the data service
        $stats = $this->departmentDataService->getDepartmentStatistics($department->slug);

        // Get recent tickets for the department
        $recentTickets = Ticket::with(['assignedResolver'])
            ->where('assigned_department_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Departments/Show', [
            'department' => $department,
            'statistics' => $stats,
            'recent_tickets' => $recentTickets,
            'user' => [
                'is_admin' => $user->is_admin,
                'department_id' => $user->department_id
            ]
        ]);
    }

    /**
     * Get department resolvers - RENDER WITH DATA
     */
    public function resolvers(Request $request, $id): Response
    {
        $user = $request->user();
        
        // Resolvers can only see their own department's resolvers
        if (!$user->is_admin && $user->department_id != $id) {
            abort(403, 'You can only access your own department');
        }

        $department = Department::findOrFail($id);
        $resolvers = Resolver::where('department_id', $id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Departments/Resolvers', [
            'resolvers' => $resolvers,
            'department' => $department,
            'user' => [
                'is_admin' => $user->is_admin
            ]
        ]);
    }

    /**
     * Get department tickets with filtering - RENDER WITH DATA
     */
    public function tickets(Request $request, $id): Response
    {
        $user = $request->user();
        
        // Resolvers can only see their own department's tickets
        if (!$user->is_admin && $user->department_id != $id) {
            abort(403, 'You can only access your own department');
        }

        $department = Department::findOrFail($id);
        
        // Use the data service to get department tickets
        $tickets = $this->departmentDataService->getDepartmentTickets($department->slug, $request->all());

        // Get available resolvers for filter dropdown
        $resolvers = Resolver::where('department_id', $id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Departments/Tickets', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'priority', 'category', 'assigned_to', 'search', 'per_page']),
            'department' => $department,
            'resolvers' => $resolvers,
            'user' => [
                'is_admin' => $user->is_admin
            ]
        ]);
    }

    /**
     * Get department overview data for AJAX requests
     */
    public function getDepartmentData(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user->is_admin && $user->department_id != $id) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You can only access your own department'
            ], 403);
        }

        $department = Department::find($id);
        
        if (!$department) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Department not found'
            ], 404);
        }

        $data = $this->departmentDataService->getDepartmentOverview($department->slug);

        return response()->json([
            'statistics' => $data['statistics'],
            'recentTickets' => $data['recent_tickets']->items(),
            'chartData' => $data['chart_data']
        ]);
    }

    /**
     * Get department chart data for AJAX requests
     */
    public function getDepartmentChartData(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user->is_admin && $user->department_id != $id) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You can only access your own department'
            ], 403);
        }

        $department = Department::find($id);
        
        if (!$department) {
            return response()->json([
                'error' => 'Not found',
                'message'=>'Department not found'
            ], 404);
        }

        $timeRange = $request->get('timeRange', '90d');
        $chartData = $this->departmentDataService->getDepartmentChartData($department->slug, $timeRange);

        return response()->json($chartData);
    }

    /**
     * Get filtered department tickets for AJAX requests
     */
    public function getFilteredDepartmentTickets(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user->is_admin && $user->department_id != $id) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You can only access your own department'
            ], 403);
        }

        $department = Department::find($id);
        
        if (!$department) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Department not found'
            ], 404);
        }

        $tickets = $this->departmentDataService->getDepartmentTickets($department->slug, $request->all());

        return response()->json([
            'tickets' => $tickets,
            'filters' => $request->all()
        ]);
    }
}