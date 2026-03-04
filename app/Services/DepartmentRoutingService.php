<?php
// File: app/Services/DepartmentRoutingService.php

namespace App\Services;

use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentRoutingService
{
    /**
     * Route a ticket to the appropriate department based on the recipant field
     */
    public function routeTicket(Ticket $ticket): ?Department
    {
        // The recipant field should contain the department name
        $recipantDepartment = $ticket->recipant;
        
        if (empty($recipantDepartment)) {
            return null;
        }
        
        // Find the department by name (exact match first)
        $department = Department::where('name', $recipantDepartment)->first();
        
        if (!$department) {
            // Try case-insensitive match
            $department = Department::whereRaw('LOWER(name) = LOWER(?)', [$recipantDepartment])->first();
        }
        
        if (!$department) {
            // Try partial match for common variations
            $department = $this->findDepartmentByVariation($recipantDepartment);
        }
        
        return $department;
    }

    /**
     * Find department by common name variations
     */
    private function findDepartmentByVariation(string $recipant): ?Department
    {
        $variations = [
            'IT Directorate' => ['IT', 'Information Technology', 'Tech Support', 'Technical', 'Computer'],
            'Finance Directorate' => ['Finance', 'Accounting', 'Billing', 'Payroll', 'Financial'],
            'Human Resource Directorate' => ['HR', 'Human Resources', 'Personnel', 'Recruitment', 'Employment'],
            'Marketing and Communication Directorate' => ['Marketing', 'Advertising', 'Promotions', 'Communications', 'PR'],
            'Operation Directorate' => ['Operations', 'Ops', 'Operational', 'Facilities'],
            'Plan and Strategy Directorate' => ['Planning', 'Strategy', 'Business Plan', 'Strategic'],
            'Share Directorate' => ['Shares', 'Stock', 'Equity', 'Investments'],
            'Compliance and Audit Directorate' => ['Compliance', 'Audit', 'Regulatory', 'Legal Compliance'],
            'Law Directorate' => ['Legal', 'Law', 'Attorney', 'Counsel'],
        ];
        
        foreach ($variations as $deptName => $terms) {
            foreach ($terms as $term) {
                if (stripos($recipant, $term) !== false) {
                    return Department::where('name', $deptName)->first();
                }
            }
        }
        
        return null;
    }

    /**
     * Store ticket reference in department table
     */
    public function storeInDepartmentTable(Ticket $ticket, Department $department): bool
    {
        $tableName = 'dept_' . $department->slug . '_tickets';
        
        try {
            // Check if table exists first
            if (!\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
                \Log::error("Department table does not exist: {$tableName}");
                return false;
            }
            
            // Check if ticket already exists in department table
            $exists = DB::table($tableName)
                ->where('ticket_id', $ticket->id)
                ->exists();
            
            if (!$exists) {
                DB::table($tableName)->insert([
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                return true;
            }
            
            return true; // Already exists, consider this success
            
        } catch (\Exception $e) {
            \Log::error("Failed to store ticket in department table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all department ticket counts for overview
     */
    public function getDepartmentTicketCounts(): array
    {
        $departments = Department::all();
        $counts = [];
        
        foreach ($departments as $department) {
            $tableName = 'dept_' . $department->slug . '_tickets';
            
            if (\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
                $counts[$department->name] = DB::table($tableName)->count();
            } else {
                $counts[$department->name] = 0;
            }
        }
        
        return $counts;
    }
}