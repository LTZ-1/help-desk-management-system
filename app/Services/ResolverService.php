<?php

namespace App\Services;

use App\Models\User;
use App\Models\Ticket;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResolverService
{
    /**
     * Get department resolvers with statistics
     */
    public function getDepartmentResolvers(int $departmentId, array $filters = []): array
    {
        $query = DB::table('users')
            ->where('department_id', $departmentId)
            ->where('is_resolver', true);

        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $resolvers = $query->select([
            'id', 'name', 'email', 'branch', 'phone', 'is_active', 
            'is_admin', 'last_login', 'created_at'
        ])->orderBy('name')->get();

        // Add statistics for each resolver
        $resolversWithStats = $resolvers->map(function ($resolver) {
            $stats = $this->getResolverStatistics($resolver->id);
            
            return [
                'id' => $resolver->id,
                'name' => $resolver->name,
                'email' => $resolver->email,
                'branch' => $resolver->branch,
                'phone' => $resolver->phone,
                'is_active' => $resolver->is_active,
                'is_admin' => $resolver->is_admin,
                'last_login' => $resolver->last_login,
                'created_at' => $resolver->created_at,
                'statistics' => $stats
            ];
        });

        return $resolversWithStats->toArray();
    }

    /**
     * Get resolver statistics
     */
    public function getResolverStatistics(int $resolverId): array
    {
        $baseQuery = Ticket::where('assigned_resolver_id', $resolverId);

        return [
            'tickets_assigned' => $baseQuery->count(),
            'tickets_resolved' => $baseQuery->where('status', 'resolved')->count(),
            'tickets_in_progress' => $baseQuery->where('status', 'in_progress')->count(),
            'tickets_overdue' => $baseQuery
                ->where('due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'avg_resolution_time' => $this->getAverageResolutionTime($resolverId),
            'resolution_rate' => $this->getResolutionRate($resolverId)
        ];
    }

    /**
     * Get resolver details with full information
     */
    public function getResolverDetails(int $resolverId): ?array
    {
        $resolver = User::where('id', $resolverId)
            ->where('is_resolver', true)
            ->first();

        if (!$resolver) {
            return null;
        }

        $department = Department::find($resolver->department_id);
        $statistics = $this->getResolverStatistics($resolverId);
        $recentTickets = $this->getResolverRecentTickets($resolverId);

        return [
            'id' => $resolver->id,
            'name' => $resolver->name,
            'email' => $resolver->email,
            'branch' => $resolver->branch,
            'phone' => $resolver->phone,
            'is_active' => $resolver->is_active,
            'is_admin' => $resolver->is_admin,
            'last_login' => $resolver->last_login,
            'created_at' => $resolver->created_at,
            'department' => $department ? [
                'id' => $department->id,
                'name' => $department->name,
                'slug' => $department->slug
            ] : null,
            'statistics' => $statistics,
            'recent_tickets' => $recentTickets
        ];
    }

    /**
     * Activate resolver
     */
    public function activateResolver(int $resolverId): array
    {
        $resolver = User::where('id', $resolverId)
            ->where('is_resolver', true)
            ->firstOrFail();

        $resolver->update(['is_active' => true]);

        return [
            'success' => true,
            'message' => "Resolver {$resolver->name} has been activated",
            'resolver' => $resolver->fresh()
        ];
    }

    /**
     * Suspend resolver
     */
    public function suspendResolver(int $resolverId): array
    {
        $resolver = User::where('id', $resolverId)
            ->where('is_resolver', true)
            ->firstOrFail();

        // Check if resolver has active assignments
        $activeTickets = Ticket::where('assigned_resolver_id', $resolverId)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();

        if ($activeTickets > 0) {
            throw ValidationException::withMessages([
                'resolver' => "Cannot suspend resolver with {$activeTickets} active tickets. Please reassign tickets first."
            ]);
        }

        $resolver->update(['is_active' => false]);

        return [
            'success' => true,
            'message' => "Resolver {$resolver->name} has been suspended",
            'resolver' => $resolver->fresh()
        ];
    }

    /**
     * Bulk suspend/activate resolvers
     */
    public function bulkUpdateResolverStatus(array $resolverIds, string $status): array
    {
        $results = [];
        $errors = [];

        DB::transaction(function () use ($resolverIds, $status, &$results, &$errors) {
            $resolvers = User::whereIn('id', $resolverIds)
                ->where('is_resolver', true)
                ->get();

            foreach ($resolvers as $resolver) {
                try {
                    if ($status === 'activate') {
                        $result = $this->activateResolver($resolver->id);
                    } elseif ($status === 'suspend') {
                        $result = $this->suspendResolver($resolver->id);
                    } else {
                        throw ValidationException::withMessages([
                            'status' => 'Invalid status. Use activate or suspend.'
                        ]);
                    }
                    
                    $results[] = $result;
                } catch (\Exception $e) {
                    $errors[] = "Resolver {$resolver->name}: " . $e->getMessage();
                }
            }
        });

        return [
            'success' => count($errors) === 0,
            'results' => $results,
            'errors' => $errors,
            'updated_count' => count($results),
            'error_count' => count($errors)
        ];
    }

    /**
     * Get resolver's recent tickets
     */
    private function getResolverRecentTickets(int $resolverId, int $limit = 5): array
    {
        return Ticket::where('assigned_resolver_id', $resolverId)
            ->with(['assignedDepartment'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at,
                    'due_date' => $ticket->due_date,
                    'department' => $ticket->assignedDepartment?->name
                ];
            })
            ->toArray();
    }

    /**
     * Get average resolution time for resolver
     */
    private function getAverageResolutionTime(int $resolverId): ?float
    {
        $resolvedTickets = Ticket::where('assigned_resolver_id', $resolverId)
            ->where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->get();

        if ($resolvedTickets->isEmpty()) {
            return null;
        }

        $totalMinutes = $resolvedTickets->sum(function ($ticket) {
            return $ticket->created_at->diffInMinutes($ticket->resolved_at);
        });

        return round($totalMinutes / $resolvedTickets->count(), 2);
    }

    /**
     * Get resolution rate for resolver
     */
    private function getResolutionRate(int $resolverId): float
    {
        $totalAssigned = Ticket::where('assigned_resolver_id', $resolverId)->count();
        
        if ($totalAssigned === 0) {
            return 0.0;
        }

        $resolvedCount = Ticket::where('assigned_resolver_id', $resolverId)
            ->where('status', 'resolved')
            ->count();

        return round(($resolvedCount / $totalAssigned) * 100, 2);
    }

    /**
     * Update resolver last login
     */
    public function updateLastLogin(int $resolverId): void
    {
        User::where('id', $resolverId)
            ->where('is_resolver', true)
            ->update(['last_login' => now()]);
    }

    /**
     * Get available resolvers for assignment (active only)
     */
    public function getAvailableResolvers(int $departmentId): array
    {
        return User::where('department_id', $departmentId)
            ->where('is_resolver', true)
            ->where('is_active', true)
            ->select(['id', 'name', 'email', 'is_admin'])
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Check if resolver can be assigned tickets
     */
    public function canAssignTickets(int $resolverId): bool
    {
        $resolver = User::where('id', $resolverId)
            ->where('is_resolver', true)
            ->first();

        return $resolver && $resolver->is_active;
    }

    /**
     * Get resolver workload (current active assignments)
     */
    public function getResolverWorkload(int $resolverId): array
    {
        $activeTickets = Ticket::where('assigned_resolver_id', $resolverId)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->with(['assignedDepartment'])
            ->get();

        return [
            'active_tickets_count' => $activeTickets->count(),
            'overdue_tickets_count' => $activeTickets
                ->where('due_date', '<', now())
                ->count(),
            'high_priority_count' => $activeTickets
                ->whereIn('priority', ['high', 'critical'])
                ->count(),
            'active_tickets' => $activeTickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'priority' => $ticket->priority,
                    'due_date' => $ticket->due_date,
                    'is_overdue' => $ticket->due_date && $ticket->due_date->isPast()
                ];
            })->toArray()
        ];
    }

    /**
     * Create new resolver
     */
    public function createResolver(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $resolver = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'department_id' => $data['department_id'],
                'branch' => $data['branch'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_resolver' => true,
                'is_admin' => $data['is_admin'] ?? false,
                'is_active' => true,
                'email_verified_at' => now()
            ]);

            return [
                'success' => true,
                'message' => "Resolver {$resolver->name} created successfully",
                'resolver' => $resolver->fresh()
            ];
        });
    }

    /**
     * Update resolver information
     */
    public function updateResolver(int $resolverId, array $data): array
    {
        $resolver = User::where('id', $resolverId)
            ->where('is_resolver', true)
            ->firstOrFail();

        $allowedFields = ['name', 'email', 'branch', 'phone', 'is_admin'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $resolver->update($updateData);

        return [
            'success' => true,
            'message' => "Resolver {$resolver->name} updated successfully",
            'resolver' => $resolver->fresh()
        ];
    }
}
