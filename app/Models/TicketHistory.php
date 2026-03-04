<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TicketHistory Model
 * 
 * Maintains a comprehensive audit log of all changes made to tickets. Records
 * who made changes, what changes were made, and when. Essential for tracking
 * ticket lifecycle and providing accountability.
 */
class TicketHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'resolver_id',
        'action',
        'description',
        'changes'
    ];

    protected $casts = [
        'changes' => 'array'
    ];

    /**
     * Relationship: History entry belongs to a ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Relationship: History entry was created by a resolver
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(Resolver::class);
    }

    /**
     * Scope: Get histories for specific action types
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Get histories by resolver
     */
    public function scopeByResolver($query, $resolverId)
    {
        return $query->where('resolver_id', $resolverId);
    }

    /**
     * Create a history entry with changes tracking
     */
    public static function log($ticketId, $resolverId, $action, $description, $changes = null)
    {
        return static::create([
            'ticket_id' => $ticketId,
            'resolver_id' => $resolverId,
            'action' => $action,
            'description' => $description,
            'changes' => $changes
        ]);
    }
}