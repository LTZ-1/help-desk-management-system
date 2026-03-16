<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRouting extends Model
{
    protected $table = 'ticket_routing';
    
    protected $fillable = [
        'ticket_id',
        'from_department_id',
        'to_department_id',
        'routing_type',
        'routing_notes',
        'routed_by'
    ];

    protected $casts = [
        'routing_type' => 'string'
    ];

    /**
     * Get the ticket that was routed
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the source department
     */
    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    /**
     * Get the destination department
     */
    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    /**
     * Get the user who routed the ticket
     */
    public function routedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'routed_by');
    }

    /**
     * Scope for initial routing
     */
    public function scopeInitial($query)
    {
        return $query->where('routing_type', 'initial');
    }

    /**
     * Scope for forwarded tickets
     */
    public function scopeForwarded($query)
    {
        return $query->where('routing_type', 'forward');
    }

    /**
     * Create initial routing for a ticket
     */
    public static function routeTicket(Ticket $ticket, Department $toDepartment, User $routedBy = null, $notes = null)
    {
        return self::create([
            'ticket_id' => $ticket->id,
            'to_department_id' => $toDepartment->id,
            'routing_type' => 'initial',
            'routing_notes' => $notes,
            'routed_by' => $routedBy?->id
        ]);
    }

    /**
     * Forward ticket to another department
     */
    public static function forwardTicket(Ticket $ticket, Department $fromDepartment, Department $toDepartment, User $forwardedBy, $notes = null)
    {
        return self::create([
            'ticket_id' => $ticket->id,
            'from_department_id' => $fromDepartment->id,
            'to_department_id' => $toDepartment->id,
            'routing_type' => 'forward',
            'routing_notes' => $notes,
            'routed_by' => $forwardedBy->id
        ]);
    }
}
