<?php

namespace App\Services;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TicketNumberService
{
    /**
     * Generate a unique, concurrency-safe sequential ticket number.
     * Format: TKT-YYYY-000001
     *
     * @param Carbon|null $date
     * @return string
     */
    public static function generate(?Carbon $date = null): string
    {
        $year = ($date ?: Carbon::now())->format('Y');
        $prefix = \App\Models\Setting::get('ticket_prefix', config('sla.ticket_id_prefix', 'TKT')) . '-' . $year . '-';

        return DB::transaction(function () use ($prefix, $year) {
            // Pessimistic lock on the latest ticket of this year to prevent sequence collisions
            $latestTicket = Ticket::where('ticket_number', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $nextSequence = 1;
            if ($latestTicket) {
                $lastNumber = (int) substr($latestTicket->ticket_number, strlen($prefix));
                $nextSequence = $lastNumber + 1;
            }

            // Ensure padded 6 digits (e.g. 000001) for future scalability
            $ticketNumber = $prefix . str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);

            // Double check uniqueness (safeguard)
            while (Ticket::where('ticket_number', $ticketNumber)->exists()) {
                $nextSequence++;
                $ticketNumber = $prefix . str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
            }

            return $ticketNumber;
        });
    }
}
