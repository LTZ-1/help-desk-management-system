<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_resolver',
        'department_id',
        'phone',
        'is_active',
        'last_login'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_resolver' => 'boolean',
            'is_active' => 'boolean',
            'last_login' => 'datetime',
        ];
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    
 public function assignedTickets()
{
    return $this->hasMany(Ticket::class, 'assigned_resolver_id');
}
     public function individualTickets()
    {
        return $this->assignedTickets()->wherePivot('assignment_type', 'individual');
    }

    public function groupTickets()
    {
        return $this->assignedTickets()->wherePivot('assignment_type', 'group');
    }

    

    public function activeTickets()
    {
        return $this->assignedTickets()->wherePivotIn('status', ['assigned', 'in_progress']);
    }

    public function resolverTickets()
{
    return $this->belongsToMany(Ticket::class, 'resolver_tickets')
                ->withPivot('assignment_type', 'status', 'notes', 'assigned_at', 'resolved_at')
                ->withTimestamps();
}

    /**
     * Check if user is a department admin
     */
    public function isDepartmentAdmin(): bool
    {
        return $this->is_admin && $this->department_id;
    }

    /**
     * Check if user is a resolver
     */
    public function isResolverRole(): bool
    {
        return $this->is_resolver && $this->department_id;
    }

    /**
     * Check if user can manage department tickets
     */
    public function canManageDepartmentTickets(): bool
    {
        return $this->isDepartmentAdmin();
    }

    /**
     * Get resolvers in the same department
     */
    public function departmentResolvers()
    {
        return User::where('department_id', $this->department_id)
                   ->where('is_resolver', true)
                   ->where('is_active', true)
                   ->where('id', '!=', $this->id);
    }

    /**
     * Get tickets created by this user
     */
    public function createdTickets()
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    /**
     * Get department tickets for admin
     */
    public function departmentTickets()
    {
        if (!$this->department_id) {
            return collect();
        }
        
        $tableName = 'dept_' . $this->department->slug . '_tickets';
        return DB::table($tableName)
                ->join('tickets', 'tickets.id', '=', $tableName . '.ticket_id')
                ->select($tableName . '.*', 'tickets.subject', 'tickets.category', 'tickets.priority', 'tickets.status', 'tickets.created_at')
                ->orderBy('tickets.created_at', 'desc');
    }
}
