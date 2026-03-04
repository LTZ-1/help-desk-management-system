<?php
// app/Services/ResolverDataService.php

namespace App\Services;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class ResolverDataService
{
    /**
     * Get resolver statistics
     */
    public function getResolverStatistics($resolverId)
    {
        return [
            'total_tickets' => $this->getTotalTickets($resolverId),
            'assigned_tickets' => $this->getAssignedTickets($resolverId),
            'in_progress_tickets' => $this->getInProgressTickets($resolverId),
            'resolved_tickets' => $this->getResolvedTickets($resolverId),
            'overdue_tickets' => $this->getOverdueTickets($resolverId),
            'group_tickets' => $this->getGroupTickets($resolverId),
            'individual_tickets' => $this->getIndividualTickets($resolverId)
        ];
    }

    /**
     * Get resolver tickets with filtering
     */
    public function getResolverTickets($resolverId, $filters = [])
    {
        $query = Ticket::with(['assignedDepartment', 'assignedResolver', 'resolvers'])
            ->whereHas('resolvers', function($q) use ($resolverId) {
                $q->where('resolver_id', $resolverId);
            });

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
        // Apply search filter (ticket number only)
        if (!empty($filters['search'])) {
            $query->where('ticket_number', 'like', '%' . $filters['search'] . '%');
        }
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('subject', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('ticket_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;

        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get resolver chart data
     */
    public function getResolverChartData($resolverId, $timeRange = '90d')
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

        // Get ticket resolution data for the chart
        $ticketsByDate = DB::table('resolver_tickets')
            ->join('tickets', 'resolver_tickets.ticket_id', '=', 'tickets.id')
            ->where('resolver_tickets.resolver_id', $resolverId)
            ->whereBetween('resolver_tickets.resolved_at', [$startDate, $endDate])
            ->selectRaw('DATE(resolver_tickets.resolved_at) as date, COUNT(*) as count')
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
                'resolved_tickets' => $count
            ];
            
            $currentDate->addDay();
        }

        return $chartData;
    }

    /**
     * Get group members for a ticket
     */
    public function getGroupMembers($ticketId)
    {
        return DB::table('resolver_tickets')
            ->join('users', 'resolver_tickets.resolver_id', '=', 'users.id')
            ->where('resolver_tickets.ticket_id', $ticketId)
            ->where('resolver_tickets.assignment_type', 'group')
            ->select('users.id', 'users.name', 'resolver_tickets.status')
            ->get();
    }

    // Private helper methods
    private function getTotalTickets($resolverId)
    {
        return DB::table('resolver_tickets')
            ->where('resolver_id', $resolverId)
            ->count();
    }

    private function getAssignedTickets($resolverId)
    {
        return DB::table('resolver_tickets')
            ->where('resolver_id', $resolverId)
            ->where('status', 'assigned')
            ->count();
    }

    private function getInProgressTickets($resolverId)
    {
        return DB::table('resolver_tickets')
            ->where('resolver_id', $resolverId)
            ->where('status', 'in_progress')
            ->count();
    }

    private function getResolvedTickets($resolverId)
    {
        return DB::table('resolver_tickets')
            ->where('resolver_id', $resolverId)
            ->where('status', 'resolved')
            ->count();
    }

    private function getOverdueTickets($resolverId)
    {
        return DB::table('resolver_tickets')
            ->join('tickets', 'resolver_tickets.ticket_id', '=', 'tickets.id')
            ->where('resolver_tickets.resolver_id', $resolverId)
            ->where('tickets.due_date', '<', now())
            ->whereNotIn('resolver_tickets.status', ['resolved'])
            ->count();
    }

    private function getGroupTickets($resolverId)
    {
        return DB::table('resolver_tickets')
            ->where('resolver_id', $resolverId)
            ->where('assignment_type', 'group')
            ->count();
    }

    private function getIndividualTickets($resolverId)
    {
        return DB::table('resolver_tickets')
            ->where('resolver_id', $resolverId)
            ->where('assignment_type', 'individual')
            ->count();
    }
}