<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'sla_status' => $this->sla_status,
            'sla_due_at' => $this->sla_due_at?->toIso8601String(),
            'sla_breached_at' => $this->sla_breached_at?->toIso8601String(),
            'is_escalated' => (bool) $this->is_escalated,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'email' => $this->creator?->email,
            ],
            'assignee' => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'email' => $this->assignee->email,
            ] : null,
            'replies_count' => $this->whenCounted('replies'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
