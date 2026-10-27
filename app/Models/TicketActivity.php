<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    use HasFactory;

    public const TYPE_CREATED = 'created';
    public const TYPE_ASSIGNED = 'assigned';
    public const TYPE_STATUS_CHANGED = 'status_changed';
    public const TYPE_PRIORITY_CHANGED = 'priority_changed';
    public const TYPE_REPLIED = 'replied';
    public const TYPE_INTERNAL_NOTE = 'internal_note';
    public const TYPE_ESCALATED = 'escalated';
    public const TYPE_IMPORTED = 'imported';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'type',
        'description',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
