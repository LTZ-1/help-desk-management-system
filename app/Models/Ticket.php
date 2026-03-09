<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Ticket extends Model
{
    //
          use SoftDeletes;
     protected $fillable = [
        'brunch', 'department', 'recipant', 'subject', 'description', 'category', 'priority', 'attachment','requester_id', // This is still needed in the model
        'requester_type',
        'requester_name',
        'requester_email',

         // NEW FIELDS FOR ASSIGNMENT SYSTEM
        'status',
        'assigned_department_id',
        'assigned_resolver_id',
        'due_date',
        'resolved_at',
        'closed_at',
        'ticket_number','assignment_type','group_id'
    ];
    
    // ADD CASTS FOR NEW DATE FIELDS
    protected $casts = [
        'due_date' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime'
    ];
 
    // SET DEFAULT VALUES
    protected $attributes = [
        'status' => 'open',
        'assignment_type' => 'individual'
    ];

    /**
     * RELATIONSHIPS - NEW FUNCTIONALITY
     */

    /**
     * Ticket belongs to an assigned department
     */
    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    /**
     * Ticket belongs to an assigned resolver
     */
    public function assignedResolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_resolver_id');
    }

    /**
     * Ticket has many assignment records
     */
    
    public function resolvers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resolver_tickets')
                    ->withPivot('assignment_type', 'status', 'notes', 'assigned_at', 'resolved_at')
                    ->withTimestamps();
    }

    public function primaryResolver()
    {
        return $this->belongsTo(User::class, 'assigned_resolver_id');
    }

    public function groupMembers()
    {
        return $this->resolvers()->wherePivot('assignment_type', 'group');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class);
    }

    /**
     * Ticket has many history entries
     */
    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class);
    }

    /**
     * BOOT METHOD - Auto-generate ticket number
     * PRESERVES EXISTING FUNCTIONALITY WHILE ADDING NEW
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            // Auto-generate ticket number if not provided
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = 'TKT-' . strtoupper(uniqid());
            }
            
            
        });
    }

    /**
     * NEW METHODS - ADDITIONAL FUNCTIONALITY
     */

    /**
     * Check if ticket is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['resolved', 'closed']);
    }

   
    /**
     * Get current assignment
     */
    public function currentAssignment()
    {
        return $this->assignments()->latest()->first();
    }

    /**
     * SCOPES - NEW FUNCTIONALITY
     */

    /**
     * Scope: Get open tickets
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope: Get assigned tickets
     */
    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_resolver_id');
    }

    /**
     * Scope: Get unassigned tickets
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_resolver_id');
    }

    /**
     * Scope: Get tickets by department
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('assigned_department_id', $departmentId);
    }

    /**
     * Scope: Get tickets by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get overdue tickets
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['resolved', 'closed']);
    }

    /**
     * ACCESSORS - PRESERVE EXISTING DATA ACCESS
     */

    /**
     * Get requester information as array
     */
    public function getRequesterAttribute()
    {
        return [
            'id' => $this->requester_id,
            'type' => $this->requester_type,
            'name' => $this->requester_name,
            'email' => $this->requester_email
        ];
    }

    /**
     * Get formatted due date
     */
    public function getFormattedDueDateAttribute()
    {
        return $this->due_date?->format('M d, Y H:i');
    }

    /**
     * MUTATORS - DATA TRANSFORMATION
     */

    /**
     * Ensure category is stored in consistent format
     */
    public function setCategoryAttribute($value)
    {
        $this->attributes['category'] = ucwords(strtolower(trim($value)));
    }

    /**
     * Ensure priority is stored in consistent format
     */
    public function setPriorityAttribute($value)
    {
        $this->attributes['priority'] = ucfirst(strtolower(trim($value)));
    }

    /**
     * BUSINESS LOGIC METHODS
     */

    /**
     * Assign ticket to a resolver
     */
    public function assignTo(Resolver $resolver, Resolver $assigner, $dueDate = null, $notes = null)
    {
        $this->assigned_resolver_id = $resolver->id;
        $this->assigned_department_id = $resolver->department_id;
        $this->due_date = $dueDate;
        $this->status = 'assigned';
        $this->save();

        // Create assignment record
        TicketAssignment::create([
            'ticket_id' => $this->id,
            'assigned_by' => $assigner->id,
            'assigned_to' => $resolver->id,
            'notes' => $notes,
            'due_date' => $dueDate
        ]);

        // Log the assignment
        TicketHistory::log(
            $this->id,
            $assigner->id,
            'assigned',
            "Ticket assigned to {$resolver->name}",
            [
                'assigned_to' => $resolver->name,
                'due_date' => $dueDate?->format('Y-m-d H:i:s')
            ]
        );

        return $this;
    }

    /**
     * Mark ticket as resolved
     */
    public function markAsResolved(Resolver $resolver, $notes = null)
    {
        $this->status = 'resolved';
        $this->resolved_at = now();
        $this->save();

        // Complete any pending assignments
        $this->assignments()->pending()->update(['completed_at' => now()]);

        // Log the resolution
        TicketHistory::log(
            $this->id,
            $resolver->id,
            'resolved',
            "Ticket marked as resolved" . ($notes ? ": {$notes}" : ""),
            ['resolved_at' => now()->format('Y-m-d H:i:s')]
        );

        return $this;
    }
}
