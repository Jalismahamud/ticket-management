<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_AGENT = 'agent';
    public const ROLE_CUSTOMER = 'customer';

    protected $attributes = [
        'is_active' => true,
        'role' => self::ROLE_CUSTOMER,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'avatar',
        'department',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAgent(): bool
    {
        return $this->role === self::ROLE_AGENT;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function canManageTickets(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_AGENT], true);
    }

    public function ticketsCreated(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    public function ticketsAssigned(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function scopeAgents($query)
    {
        return $query->whereIn('role', [self::ROLE_AGENT, self::ROLE_ADMIN])
            ->where('is_active', true);
    }
}
