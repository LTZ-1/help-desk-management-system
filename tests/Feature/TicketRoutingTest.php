<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Ticket;
use App\Services\TicketRoutingService;

class TicketRoutingTest extends TestCase
{
    public function test_it_routes_it_tickets_correctly()
    {
        $ticket = Ticket::create([
            'brunch' => 'Main',
            'department' => 'IT',
            'recipant' => 'it-support@company.com',
            'subject' => 'Password reset request',
            'description' => 'I need to reset my password',
            'category' => 'Software',
            'priority' => 'High',
            'requester_id' => 1,
            'requester_type' => 'employee',
            'requester_name' => 'John Doe',
            'requester_email' => 'john@company.com'
        ]);

        $routingService = new TicketRoutingService();
        $result = $routingService->routeTicket($ticket);

        $this->assertTrue($result);
        $this->assertEquals('IT Directorate', $ticket->assignedDepartment->name);
    }
}