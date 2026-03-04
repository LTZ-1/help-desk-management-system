<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Resolver Model
 * 
 * Represents staff members who can resolve tickets. Extends Authenticatable
 * for login capabilities. Handles ticket assignments and department associations.
 */
class Resolver extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'is_admin',
        'phone',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Relationship: Resolver belongs to a department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relationship: Resolver has many assigned tickets
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_resolver_id');
    }

    /**
     * Relationship: Resolver has many assignment records (where they assigned tickets to others)
     */
    public function ticketAssignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class, 'assigned_by');
    }

    /**
     * Relationship: Resolver has many ticket history entries
     */
    public function ticketHistories(): HasMany
    {
        return $this->hasMany(TicketHistory::class);
    }

    /**
     * Scope: Get only admin resolvers
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope: Get only active resolvers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if resolver can access a specific department's tickets
     */
    public function canAccessDepartment($departmentId): bool
    {
        return $this->department_id == $departmentId || $this->is_admin;
    }
}