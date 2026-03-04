 <?php
// File: 2025_08_31_000000_migrate_existing_tickets_to_departments.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\Department;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all existing tickets that don't have department assignment
        $tickets = Ticket::whereNull('assigned_department_id')->get();
        
        $migratedCount = 0;
        $skippedCount = 0;
        
        foreach ($tickets as $ticket) {
            try {
                // Determine which department this ticket belongs to based on recipant
                $department = $this->routeTicketByRecipant($ticket);
                
                if ($department) {
                    $tableName = 'dept_' . $department->slug . '_tickets';
                    
                    // Check if table exists
                    if (!$this->tableExists($tableName)) {
                        \Log::warning("Department table does not exist: {$tableName}");
                        $skippedCount++;
                        continue;
                    }
                    
                    // Check if this ticket already exists in the department table
                    $exists = DB::table($tableName)
                        ->where('ticket_id', $ticket->id)
                        ->exists();
                    
                    if (!$exists) {
                        // Insert into department table
                        DB::table($tableName)->insert([
                            'ticket_id' => $ticket->id,
                            'ticket_number' => $ticket->ticket_number,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        // Update the main ticket with the assigned department
                        $ticket->assigned_department_id = $department->id;
                        $ticket->save();
                        
                        $migratedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    // If no department found, assign to a default deparement (IT)
                    $defaultDepartment = Department::where('slug', 'it')->first();
                    
                    if ($defaultDepartment) {
                        $tableName = 'dept_' . $defaultDepartment->slug . '_tickets';
                        
                        if ($this->tableExists($tableName)) {
                            DB::table($tableName)->insert([
                                'ticket_id' => $ticket->id,
                                'ticket_numb er' => $ticket->ticket_number,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            $ticket->assigned_department_id = $defaultDepartment->id;
                            $ticket->save();
                            
                            $migratedCount++;
                        } else {
                            $skippedCount++;
                        }
                    } else {
                        $skippedCount++;
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Failed to migrate ticket {$ticket->id}: " . $e->getMessage());
                $skippedCount++;
                continue;
            }
        }
        
        \Log::info("Ticket migration completed: {$migratedCount} migrated, {$skippedCount} skipped");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data migration, so we can't easily reverse it
        // We'll just leave the data in place
    }
    
    /**
     * Route ticket based on recipant field
     */
    private function routeTicketByRecipant(Ticket $ticket): ?Department
    {
        $recipantDepartment = $ticket->recipant;
        
        if (empty($recipantDepartment)) {
            return null;
        }
        
        // Find the department by name
        return Department::where('name', $recipantDepartment)->first();
    }
    
    /**
     * Check if a table exists
     */
    private function tableExists(string $tableName): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable($tableName);
    }
};