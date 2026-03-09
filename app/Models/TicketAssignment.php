<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TicketAssignment Model
 * 
 * Tracks the history of ticket assignments between resolvers. Records who assigned
 * which ticket to whom, when, and with what notes. Provides audit trail for
 * assignment changes.
 */
class TicketAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'assigned_by',
        'assigned_to',
        'department_id',
        'assignment_type',
        'group_id',
        'notes',
        'due_date',
        'completed_at',
        'assigned_at'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Relationship: Assignment belongs to a ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Relationship: Assignment was made by a resolver
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(Resolver::class, 'assigned_by');
    }

    /**
     * Relationship: Assignment was made to a resolver
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Resolver::class, 'assigned_to');
    }

    /**
     * Scope: Get completed assignments
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope: Get pending assignments
     */
    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Mark assignment as completed
     */
    public function markAsCompleted()
    {
        $this->completed_at = now();
        return $this->save();
    }
}