<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Department Model
 * * Represents the various departments in the organization that can be assigned tickets.
 * Handles department-specific routing and resolver management.
 */
class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Relationship: Department has many resolvers
     */
    public function resolvers(): HasMany
    {
        return $this->hasMany(Resolver::class);
    }

    /**
     * Relationship: Department has many assigned tickets
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_department_id');
    }

    /**
     * Scope: Get only active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get department by slug
     */
    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->first();
    }

     
    public function scopeAvailable($query)
    {
        // Check if is_active column exists in the table
        if (\Schema::hasColumn($this->getTable(), 'is_active')) {
            return $query->where('is_active', true);
        }
        
        // Fallback: return all departments if is_active column doesn't exist
        return $query;
    }
}
