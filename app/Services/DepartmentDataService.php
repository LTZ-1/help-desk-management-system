<?php
// File: app/Services/DepartmentDataService.php

namespace App\Services;

use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentDataService
{
    /**
     * Get tickets for a specific department with full details
     */
    public function getDepartmentTickets($departmentSlug, $filters = [])
    {
        $tableName = 'dept_' . $departmentSlug . '_tickets';
        
        // Check if table exists
        if (!Schema::hasTable($tableName)) {
            return new LengthAwarePaginator([], 0, 20);
        }

        // Get ticket references from department table
        $ticketRefs = DB::table($tableName)
            ->select('ticket_id', 'ticket_number')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Apply search filter if provided
        if (!empty($filters['search'])) {
            $query->where('ticket_number', 'like', '%' . $filters['search'] . '%');
        }
        
       
        
        // Get full ticket details for these references
        $ticketIds = $ticketRefs->pluck('ticket_id');
        
        if ($ticketIds->isEmpty()) {
            return new LengthAwarePaginator([], 0, 20);
        }
        
        $query = Ticket::with(['assignedDepartment', 'assignedResolver'])
                    ->whereIn('id', $ticketIds);
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        
        if (!empty($filters['category'])) {
            $query->where('category', 'like', '%' . $filters['category'] . '%');
        }
        
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('subject', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('ticket_number', 'like', '%极' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;
        
        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage, ['*'], 'page', $page);
    }
    
    /**
     * Get statistics for a specific department
     */
    public function getDepartmentStatistics($departmentSlug)
    {
        $tableName = 'dept_' . $departmentSlug . '_tickets';
        
        // Check if table exists
        if (!Schema::hasTable($tableName)) {
            return [
                'total_tickets' => 0,
                'open_tickets' => 0,
                'assigned_tickets' => 0,
                'resolved_tickets' => 0,
                'overdue_tickets' => 0,
                'active_resolvers' => 0
            ];
        }
        
        // Get ticket references from department table
        $ticketRefs = DB::table($tableName)->pluck('ticket_id');
        
        if ($ticketRefs->isEmpty()) {
            return [
                'total_tickets' => 0,
                'open_tickets' => 0,
                'assigned_tickets' => 0,
                'resolved_tickets' => 0,
                'overdue_tickets极' => 0,
                'active_resolvers' => 0
            ];
        }
        
        // Get statistics from main tickets table
        return [
            'total_tickets' => Ticket::whereIn('id', $ticketRefs)->count(),
            'open_tickets' => Ticket::whereIn('id', $ticketRefs)->where('status', 'open')->count(),
            'assigned_tickets' => Ticket::whereIn('id', $ticketRefs)->where('status', 'assigned')->count(),
            'resolved_tickets' => Ticket::whereIn('id', $ticketRefs)->where('status', 'resolved')->count(),
            'overdue_tickets' => Ticket::whereIn('id', $ticketRefs)
                ->where('due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'active_resolvers' => $this->getActiveResolversCount($departmentSlug)
        ];
    }
    
    /**
     * Get active resolvers count for a department
     */
    private function getActiveResolversCount($departmentSlug)
    {
        $department = Department::where('slug', $departmentSlug)->first();
        
        if (!$department) {
            return 0;
        }
        
        return DB::table('users')
            ->where('department_id', $department->id)
            ->where('is_resolver', true)
            ->where('is_active', true)
            ->count();
    }
    
    /**
     * Get a single ticket with full details if it belongs to the department
     */
    public function getDepartmentTicket($departmentSlug, $ticketId)
    {
        $tableName = 'dept_' . $departmentSlug . '_tickets';
        
        // Check if table exists and ticket belongs to this department
        if (!Schema::hasTable($tableName) || 
            !DB::table($tableName)->where('ticket_id', $ticketId)->exists()) {
            return null;
        }
        
        // Get full ticket details from main table
        return Ticket::with(['assignedDepartment', 'assignedResolver', 'histories'])
                    ->find($ticketId);
    }

    /**
     * Get department chart data for analytics
     */
    public function getDepartmentChartData($departmentSlug, $timeRange = '90d')
    {
        $tableName = 'dept_' . $departmentSlug . '_tickets';
        
        // Check if table exists
        if (!Schema::hasTable($tableName)) {
            return [];
        }
        
        // Get ticket references from department table
        $ticketRefs = DB::table($tableName)->pluck('ticket_id');
        
        if ($ticketRefs->isEmpty()) {
            return [];
        }
        
        // Calculate date range based on timeRange
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
        
        // Get ticket creation data for the chart
        $ticketsByDate = Ticket::whereIn('id', $ticketRefs)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Format data for the chart
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
     * Get department overview with all relevant data
     */
    public function getDepartmentOverview($departmentSlug)
    {
        return [
            'statistics' => $this->getDepartmentStatistics($departmentSlug),
            'recent_tickets' => $this->getDepartmentTickets($departmentSlug, ['per_page' => 5]),
            'chart_data' => $this->getDepartmentChartData($departmentSlug, '90d')
        ];
    }
}