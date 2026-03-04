<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
            'is_admin',
    'department_id',
    'is_resolver',
    'phone',
    'is_active'
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
}
