<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Department;
use App\Models\TicketAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TicketService
{
    /**
     * Get tickets for a specific department with enhanced filtering and sorting
     */
    public function getDepartmentTickets(int $departmentId, array $filters = []): LengthAwarePaginator
    {
        $query = Ticket::with(['assignedDepartment', 'assignedResolver'])
            ->where('assigned_department_id', $departmentId);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get tickets assigned to a specific resolver
     */
    public function getResolverTickets(int $resolverId, array $filters = []): LengthAwarePaginator
    {
        $query = Ticket::with(['assignedDepartment', 'assignedResolver'])
            ->where('assigned_resolver_id', $resolverId);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get department statistics with accurate aggregation
     */
    public function getDepartmentStatistics(int $departmentId): array
    {
        return [
            'total_tickets_to_resolve' => Ticket::where('assigned_department_id', $departmentId)
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'assigned_tickets' => Ticket::where('assigned_department_id', $departmentId)
                ->whereNotNull('assigned_resolver_id')
                ->count(),
            'resolved_tickets' => Ticket::where('assigned_department_id', $departmentId)
                ->where('status', 'resolved')
                ->count(),
            'overdue_tickets' => Ticket::where('assigned_department_id', $departmentId)
                ->where('due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'active_resolvers' => $this->getActiveResolversCount($departmentId),
            'assigned_resolver_groups' => $this->getAssignedResolverGroupsCount($departmentId)
        ];
    }

    /**
     * Get resolver statistics
     */
    public function getResolverStatistics(int $resolverId): array
    {
        $baseQuery = Ticket::where('assigned_resolver_id', $resolverId);

        return [
            'total_assigned' => $baseQuery->count(),
            'resolved' => $baseQuery->where('status', 'resolved')->count(),
            'in_progress' => $baseQuery->where('status', 'in_progress')->count(),
            'overdue' => $baseQuery
                ->where('due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count()
        ];
    }

    /**
     * Get department chart data with multiple metrics
     */
    public function getDepartmentChartData(int $departmentId, string $timeRange = '90d'): array
    {
        $endDate = now();
        $startDate = clone $endDate;
        
        switch ($timeRange) {
            case '7d':
                $startDate->subDays(7);
                break;
            case '30d':
                $startDate->subDays(30);
                break;
            default: // 90d
                $startDate->subDays(90);
                break;
        }

        // Get tickets created per day
        $ticketsByDate = Ticket::where('assigned_department_id', $departmentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as tickets')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Get tickets assigned per day
        $assignedByDate = DB::table('dept_' . Department::find($departmentId)->slug . '_tickets')
            ->join('tickets', 'tickets.id', '=', 'dept_' . Department::find($departmentId)->slug . '_tickets.ticket_id')
            ->whereNotNull('dept_' . Department::find($departmentId)->slug . '_tickets.assigned_resolver_id')
            ->whereBetween('dept_' . Department::find($departmentId)->slug . '_tickets.assigned_at', [$startDate, $endDate])
            ->selectRaw('DATE(dept_' . Department::find($departmentId)->slug . '_tickets.assigned_at) as date, COUNT(*) as assigned_tickets')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Get tickets resolved per day
        $resolvedByDate = Ticket::where('assigned_department_id', $departmentId)
            ->where('status', 'resolved')
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->selectRaw('DATE(resolved_at) as date, COUNT(*) as resolved_tickets')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Format data for the chart
        $chartData = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $chartData[] = [
                'date' => $dateStr,
                'tickets' => $ticketsByDate[$dateStr]->tickets ?? 0,
                'assigned_tickets' => $assignedByDate[$dateStr]->assigned_tickets ?? 0,
                'resolved_tickets' => $resolvedByDate[$dateStr]->resolved_tickets ?? 0
            ];
            
            $currentDate->addDay();
        }
        
        return $chartData;
    }

    /**
     * Update ticket sorting order
     */
    public function updateTicketOrder(array $ticketOrders): bool
    {
        try {
            DB::beginTransaction();
            
            foreach ($ticketOrders as $order) {
                Ticket::where('id', $order['ticket_id'])
                    ->update(['sort_order' => $order['sort_order']]);
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get single ticket with full details
     */
    public function getTicketById(int $ticketId): ?Ticket
    {
        return Ticket::with([
            'assignedDepartment', 
            'assignedResolver', 
            'histories',
            'assignments'
        ])->find($ticketId);
    }

    /**
     * Apply filters to ticket query
     */
    private function applyFilters($query, array $filters): void
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

        // Search filter (ticket number, subject, description)
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('ticket_number', 'like', '%' . $searchTerm . '%')
                  ->orWhere('subject', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Assignment type filter
        if (!empty($filters['assignment_type'])) {
            $query->where('assignment_type', $filters['assignment_type']);
        }

        // Due date filter
        if (!empty($filters['due_date_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_date_from']);
        }
        
        if (!empty($filters['due_date_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_date_to']);
        }
    }

    /**
     * Apply sorting to ticket query
     */
    private function applySorting($query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        // Validate sort column
        $allowedSortColumns = [
            'created_at', 'due_date', 'priority', 'status', 
            'ticket_number', 'subject', 'sort_order'
        ];
        
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Get count of assigned resolver groups
     */
    private function getAssignedResolverGroupsCount(int $departmentId): int
    {
        return Ticket::where('assigned_department_id', $departmentId)
            ->where('assignment_type', 'group')
            ->whereNotNull('group_id')
            ->distinct('group_id')
            ->count('group_id');
    }

    /**
     * Get tickets for bulk operations
     */
    public function getTicketsByIds(array $ticketIds): Collection
    {
        return Ticket::with(['assignedDepartment', 'assignedResolver'])
            ->whereIn('id', $ticketIds)
            ->get();
    }

    /**
     * Check if ticket can be reassigned (not closed)
     */
    public function canReassignTicket(Ticket $ticket): bool
    {
        return !in_array($ticket->status, ['closed', 'resolved']);
    }

    /**
     * Get active resolvers count for a department
     */
    private function getActiveResolversCount(int $departmentId): int
    {
        return DB::table('users')
            ->where('department_id', $departmentId)
            ->where('is_resolver', true)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Get department resolvers for assignment
     */
    public function getDepartmentResolvers(int $departmentId): Collection
    {
        return DB::table('users')
            ->where('department_id', $departmentId)
            ->where('is_resolver', true)
            ->where('is_active', true)
            ->select(['id', 'name', 'email', 'is_admin'])
            ->orderBy('name')
            ->get();
    }
}
