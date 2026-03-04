<?php
// app/Http/Controllers/ResolverDashboardController.php

namespace App\Http\Controllers;

use App\Services\ResolverDataService;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResolverDashboardController extends Controller
{
    protected $resolverDataService;

    public function __construct(ResolverDataService $resolverDataService)
    {
        $this->resolverDataService = $resolverDataService;
    }

    /**
     * Display the resolver dashboard
     */
    public function index(Request $request): Response
    {
        $resolver = $request->user();
        
        if (!$resolver->is_resolver) {
            abort(403, 'Resolver access required.');
        }

        $statistics = $this->resolverDataService->getResolverStatistics($resolver->id);
        $recentTickets = $this->resolverDataService->getResolverTickets($resolver->id, ['per_page' => 5]);
        $chartData = $this->resolverDataService->getResolverChartData($resolver->id);

        return Inertia::render('ResolverDashboard', [
            'statistics' => $statistics,
            'recentTickets' => $recentTickets->items(),
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

        $tickets = $this->resolverDataService->getResolverTickets($resolver->id, $request->all());

        return response()->json([
            'tickets' => $tickets,
            'filters' => $request->all()
        ]);
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
        $chartData = $this->resolverDataService->getResolverChartData($resolver->id, $timeRange);

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

        // Verify the resolver has access to this ticket
        $hasAccess = DB::table('resolver_tickets')
            ->where('resolver_id', $resolver->id)
            ->where('ticket_id', $ticketId)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['error' => 'Ticket not found or access denied'], 404);
        }

        $groupMembers = $this->resolverDataService->getGroupMembers($ticketId);

        return response()->json($groupMembers);
    }
}