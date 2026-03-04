<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TicketRoutingService;
use ReflectionClass;

class TicketRoutingPatternTest extends TestCase
{
    private $routingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->routingService = new TicketRoutingService();
    }

    public function test_it_can_be_instantiated()
    {
        $this->assertInstanceOf(TicketRoutingService::class, $this->routingService);
    }

    public function test_it_provides_routing_statistics()
    {
        $stats = $this->routingService->getRoutingStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('recipient_rules', $stats);
        $this->assertArrayHasKey('subject_rules', $stats);
        $this->assertArrayHasKey('category_mappings', $stats);
        
        $this->assertGreaterThan(0, $stats['recipient_rules']);
        $this->assertGreaterThan(0, $stats['subject_rules']);
        $this->assertGreaterThan(0, $stats['category_mappings']);
    }

    public function test_it_matches_it_department_patterns()
    {
        $reflection = new ReflectionClass($this->routingService);
        $method = $reflection->getMethod('routeByRecipient');
        $method->setAccessible(true);

        $itEmails = [
            'it-support@company.com',
            'helpdesk@company.com',
            'support@company.com',
            'technical@company.com',
            'admin@it.company.com'
        ];

        foreach ($itEmails as $email) {
            $result = $method->invoke($this->routingService, $email);
            $this->assertNotNull($result, "IT email pattern failed for: $email");
            $this->assertIsInt($result, "Should return integer department ID");
        }
    }

    public function test_it_matches_finance_department_patterns()
    {
        $reflection = new ReflectionClass($this->routingService);
        $method = $reflection->getMethod('routeByRecipient');
        $method->setAccessible(true);

        $financeEmails = [
            'finance@company.com',
            'accounting@company.com',
            'billing@company.com',
            'invoice@company.com',
            'payroll@company.com'
        ];

        foreach ($financeEmails as $email) {
            $result = $method->invoke($this->routingService, $email);
            $this->assertNotNull($result, "Finance email pattern failed for: $email");
            $this->assertIsInt($result, "Should return integer department ID");
        }
    }

    public function test_it_returns_null_for_unknown_patterns()
    {
        $reflection = new ReflectionClass($this->routingService);
        $method = $reflection->getMethod('routeByRecipient');
        $method->setAccessible(true);

        $unknownEmails = [
            'unknown@example.com',
            'test@gmail.com',
            'user@external.com'
        ];

        foreach ($unknownEmails as $email) {
            $result = $method->invoke($this->routingService, $email);
            $this->assertNull($result, "Unexpected match for: $email");
        }
    }

    public function test_different_departments_have_different_ids()
    {
        $reflection = new ReflectionClass($this->routingService);
        $method = $reflection->getMethod('routeByRecipient');
        $method->setAccessible(true);

        $itResult = $method->invoke($this->routingService, 'it-support@company.com');
        $financeResult = $method->invoke($this->routingService, 'finance@company.com');
        
        $this->assertNotNull($itResult);
        $this->assertNotNull($financeResult);
        $this->assertNotEquals($itResult, $financeResult, "IT and Finance should have different department IDs");
    }

    public function test_subject_pattern_matching_works()
    {
        $reflection = new ReflectionClass($this->routingService);
        $method = $reflection->getMethod('routeBySubject');
        $method->setAccessible(true);

        $itSubjects = [
            'Password reset request',
            'Network connectivity issue',
            'Software installation request'
        ];

        foreach ($itSubjects as $subject) {
            $result = $method->invoke($this->routingService, $subject);
            $this->assertNotNull($result, "IT subject pattern failed for: $subject");
            $this->assertIsInt($result, "Should return integer department ID");
        }
    }
}