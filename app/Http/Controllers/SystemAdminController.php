<?php
// app/Http/Controllers/SystemAdminController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ticket;
use App\Models\Department;
use App\Models\TicketHistory;
use App\Models\ResolverTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class SystemAdminController extends Controller
{
    /**
     * Display system administration dashboard
     */
    public function index(Request $request): Response
    {
        // Only system admins (admin without department) can access
        $user = $request->user();
        
        if (!$this->isITDepartmentAdmin($user)) {
            abort(403, 'IT Department administrator access required.');
        }

        return Inertia::render('SystemAdmin', [
            'systemStats' => $this->getSystemStatistics(),
            'userRegistrationData' => $this->getUserRegistrationData(),
            'ticketResolutionData' => $this->getTicketResolutionData(),
            'departmentDistributionData' => $this->getDepartmentDistributionData(),
            'users' => $this->getUsersList(),
            'user_has_department' => $user->department_id !== null,
            'user_is_admin' => $user->is_admin,
            'user_is_resolver' => $user->is_resolver,
            'user_is_none' => $user->is_none,
        ]);
    }

     private function isITDepartmentAdmin(User $user): bool
    {
        // Check if user is admin and belongs to IT department
        if (!$user->is_admin || !$user->department_id) {
            return false;
        }

        // Get IT department (assuming IT department has slug 'it')
        $itDepartment = Department::where('slug', 'it')->first();

        if (!$itDepartment) {
            return false;
        }

        return $user->department_id === $itDepartment->id;
    }
    /**
     * Get comprehensive system statistics
     */
    private function getSystemStatistics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        
        return [
            'total_tickets' => Ticket::count(),
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'assigned_tickets' => Ticket::where('status', 'assigned')->count(),
            'resolved_tickets'=>$this->getResolvedTicketsCount(),
            'overdue_tickets' => Ticket::where('due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'active_resolvers' => User::where('is_resolver', true)
                ->where('is_active', true)
                ->count(),
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $totalUsers - $activeUsers,
            'total_departments' => Department::count(),
            'average_resolution_time' => $this->getAverageResolutionTime(),
        ];
    }

    /**
     * Calculate average ticket resolution time in hours
     */
     private function getResolvedTicketsCount(): int
    {
        return DB::table('resolver_tickets')
            ->whereNotNull('resolved_at')
            ->count();
    }

 /**
     * Calculate average ticket resolution time in hours
     */
    private function getAverageResolutionTime(): float
    {
        $avgTime = DB::table('resolver_tickets')
            ->whereNotNull('resolved_at')
            ->whereNotNull('assigned_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, assigned_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        return round($avgTime ?? 0, 2);
    }


    /**
     * Get user registration data for charts (last 90 days)
     */
    private function getUserRegistrationData(): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays(90);

        $registrations = User::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->formatChartData($registrations, $startDate, $endDate);
    }

    /**
     * Get ticket resolution data for charts from resolver_tickets table
     */
    private function getTicketResolutionData(): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays(90);

        $resolutions = DB::table('resolver_tickets')
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->selectRaw('DATE(resolved_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->formatChartData($resolutions, $startDate, $endDate);
    }
   /**
     * Get department distribution data
     */
    private function getDepartmentDistributionData(): array
    {
        return Department::withCount(['tickets' => function($query) {
                $query->whereIn('id', function($subquery) {
                    $subquery->select('ticket_id')
                        ->from('resolver_tickets')
                        ->whereNotNull('resolved_at');
                });
            }])
            ->get()
            ->map(function($department) {
                return [
                    'name' => $department->name,
                    'value' => $department->tickets_count
                ];
            })
            ->toArray();
    }

    /**
     * Get users list for management table
     */
    private function getUsersList(): array
    {
        return User::with(['department' => function($query) {
                $query->select('id', 'name');
            }])
            ->select('id', 'name', 'email', 'is_admin', 'is_resolver', 'is_none', 'is_active', 'department_id', 'last_login', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'is_resolver' => $user->is_resolver,
                    'is_none' => $user->is_none,
                    'is_active' => $user->is_active,
                    'department_name' => $user->department->name ?? null,
                    'last_login' => $user->last_login?->toDateTimeString(),
                    'created_at' => $user->created_at->toDateTimeString(),
                ];
            })
            ->toArray();
    }

    /**
     * Format chart data with all dates filled in
     */
    private function formatChartData($data, $startDate, $endDate): array
    {
        $formattedData = [];
        $currentDate = $startDate->copy();

        // Create a lookup array for existing data
        $dataLookup = [];
        foreach ($data as $item) {
            $dataLookup[$item->date] = $item->count;
        }

        // Fill in all dates in the range
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $formattedData[] = [
                'date' => $dateStr,
                'value' => $dataLookup[$dateStr] ?? 0
            ];
            $currentDate->addDay();
        }

        return $formattedData;
    }
    /**
     * Update user status (activate/deactivate)
     */
    public function updateUserStatus(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $user->is_active = $request->is_active;
        $user->save();

        // Log the action
        TicketHistory::log(
            null, // No ticket ID for user actions
            $request->user()->id,
            'user_status_updated',
            "User {$user->name} " . ($request->is_active ? 'activated' : 'deactivated'),
            [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'new_status' => $request->is_active ? 'active' : 'inactive'
            ]
        );

        return response()->json(['message' => 'User status updated successfully']);
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, $userId)
    {
        $this->checkITAdminAccess($request->user());
        
        $user = User::findOrFail($userId);
        
        $request->validate([
            'is_admin' => 'boolean',
            'is_resolver' => 'boolean',
            'is_none' => 'boolean',
        ]);

         // Ensure only one role is set
        if ($request->is_admin + $request->is_resolver + $request->is_none > 1) {
            return response()->json(['error' => 'User can only have one role'], 422);
        }

        $user->update($request->only(['is_admin', 'is_resolver', 'is_none']));


         // Ensure only one role is set
        if ($request->is_admin + $request->is_resolver + $request->is_none > 1) {
            return response()->json(['error' => 'User can only have one role'], 422);
        }

        $user->update($request->only(['is_admin', 'is_resolver', 'is_none']));}
    
  /**
     * Get system settings (placeholder for future implementation)
     */
    public function getSystemSettings(Request $request)
    {
        $this->checkITAdminAccess($request->user());
        
        return response()->json([
            'session_timeout' => config('session.lifetime', 120),
            'password_policy' => [
                'min_length' => 8,
                'require_special_chars' => true,
                'require_numbers' => true,
            ],
            'maintenance_mode' => false,
        ]);
    }
    /**
     * Check IT admin access
     */
    private function checkITAdminAccess(User $user): void
    {
        if (!$this->isITDepartmentAdmin($user)) {
            abort(403, 'IT Department administrator access required.');
        }
    }

    /**
     * Get IT department
     */
    private function getITDepartment(): ?Department
    {
        return Department::where('slug', 'it')->first();
    }
}