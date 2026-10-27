<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_CUSTOMER = 'waiting_for_customer';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    protected $fillable = [
        'ticket_number',
        'subject',
        'description',
        'priority',
        'status',
        'category_id',
        'created_by',
        'assigned_to',
        'sla_due_at',
        'sla_breached_at',
        'is_escalated',
        'escalated_at',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'last_reply_at',
    ];

    protected function casts(): array
    {
        return [
            'sla_due_at' => 'datetime',
            'sla_breached_at' => 'datetime',
            'escalated_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_reply_at' => 'datetime',
            'is_escalated' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function publicReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->where('is_internal', false);
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(TicketReply::class)->where('is_internal', true);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->latest();
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }

    public function isSlaBreached(): bool
    {
        if ($this->sla_breached_at) {
            return true;
        }

        if ($this->sla_due_at && !$this->isTerminal()) {
            return Carbon::now()->greaterThan($this->sla_due_at);
        }

        return false;
    }

    public function isSlaApproaching(): bool
    {
        if ($this->isTerminal() || $this->isSlaBreached() || !$this->sla_due_at) {
            return false;
        }

        $approachingThresholdMinutes = config('sla.approaching_warning_minutes', 120);
        $diffInMinutes = Carbon::now()->diffInMinutes($this->sla_due_at, false);

        return $diffInMinutes > 0 && $diffInMinutes <= $approachingThresholdMinutes;
    }

    public function getSlaStatusAttribute(): string
    {
        if ($this->isTerminal()) {
            return $this->sla_breached_at ? 'breached_resolved' : 'resolved_within_sla';
        }

        if ($this->isSlaBreached()) {
            return 'breached';
        }

        if ($this->isSlaApproaching()) {
            return 'approaching';
        }

        return 'within_sla';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_CUSTOMER,
        ]);
    }

    public function scopeClosedOrResolved(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ]);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isAgent()) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }

    public function scopeSlaBreached(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED])
            ->whereNotNull('sla_due_at')
            ->where(function ($q) {
                $q->whereNotNull('sla_breached_at')
                  ->orWhere('sla_due_at', '<', now());
            });
    }

    public function scopeSlaApproaching(Builder $query, int $warningMinutes = 120): Builder
    {
        $now = Carbon::now();
        $threshold = $now->copy()->addMinutes($warningMinutes);

        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED])
            ->whereNull('sla_breached_at')
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '>=', $now)
            ->where('sla_due_at', '<=', $threshold);
    }
}
