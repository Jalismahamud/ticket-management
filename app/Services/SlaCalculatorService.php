<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class SlaCalculatorService
{
    /**
     * Calculate SLA resolution deadline based on priority and business hours.
     *
     * @param string $priority ('low', 'medium', 'high', 'critical')
     * @param Carbon|null $from
     * @return Carbon
     */
    public static function calculateDeadline(string $priority, ?Carbon $from = null): Carbon
    {
        $hours = (int) \App\Models\Setting::get("sla_{$priority}", config("sla.priorities.{$priority}", 24));
        $totalMinutes = $hours * 60;

        return self::addBusinessMinutes($totalMinutes, $from ?: Carbon::now());
    }

    /**
     * Add business minutes to a timestamp, skipping non-working hours and weekends/holidays.
     *
     * @param int $remainingMinutes
     * @param Carbon $time
     * @return Carbon
     */
    public static function addBusinessMinutes(int $remainingMinutes, Carbon $time): Carbon
    {
        $current = $time->copy();
        $businessDays = (array) \App\Models\Setting::get('business_days', config('sla.business_days', [
            CarbonInterface::SUNDAY,
            CarbonInterface::MONDAY,
            CarbonInterface::TUESDAY,
            CarbonInterface::WEDNESDAY,
            CarbonInterface::THURSDAY,
        ]));

        [$startHour, $startMinute] = explode(':', (string) \App\Models\Setting::get('business_start', config('sla.business_hours.start', '09:00')));
        [$endHour, $endMinute] = explode(':', (string) \App\Models\Setting::get('business_end', config('sla.business_hours.end', '18:00')));

        $startHour = (int) $startHour;
        $startMinute = (int) $startMinute;
        $endHour = (int) $endHour;
        $endMinute = (int) $endMinute;

        while ($remainingMinutes > 0) {
            // 1. If not a business day, roll forward to 00:00 of next day
            if (!in_array($current->dayOfWeek, $businessDays, true)) {
                $current->addDay()->setTime($startHour, $startMinute, 0);
                continue;
            }

            $businessStart = $current->copy()->setTime($startHour, $startMinute, 0);
            $businessEnd = $current->copy()->setTime($endHour, $endMinute, 0);

            // 2. If current time is before business start, snap to business start
            if ($current->lessThan($businessStart)) {
                $current = $businessStart->copy();
            }

            // 3. If current time is after business end, roll to next business day start
            if ($current->greaterThanOrEqualTo($businessEnd)) {
                $current->addDay()->setTime($startHour, $startMinute, 0);
                continue;
            }

            // 4. Calculate available business minutes remaining today
            $availableMinutesToday = $current->diffInMinutes($businessEnd, false);

            if ($remainingMinutes <= $availableMinutesToday) {
                // All remaining minutes fit in the current business day
                $current->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                // Consume all available minutes today and advance to next business day
                $remainingMinutes -= $availableMinutesToday;
                $current->addDay()->setTime($startHour, $startMinute, 0);
            }
        }

        return $current;
    }

    /**
     * Check if a given date is a business day.
     */
    public static function isBusinessDay(Carbon $date): bool
    {
        $businessDays = (array) \App\Models\Setting::get('business_days', config('sla.business_days', [
            CarbonInterface::SUNDAY,
            CarbonInterface::MONDAY,
            CarbonInterface::TUESDAY,
            CarbonInterface::WEDNESDAY,
            CarbonInterface::THURSDAY,
        ]));

        return in_array($date->dayOfWeek, $businessDays, true);
    }
}
