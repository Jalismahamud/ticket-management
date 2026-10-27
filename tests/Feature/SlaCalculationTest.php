<?php

use App\Services\SlaCalculatorService;
use Carbon\Carbon;

test('critical priority ticket created on thursday at 5pm resolves to sunday at 10am', function () {
    // Thursday 2026-03-05 at 17:00 (5:00 PM)
    $thursday5pm = Carbon::parse('2026-03-05 17:00:00');
    expect($thursday5pm->englishDayOfWeek)->toBe('Thursday');

    // Critical SLA = 2 hours (120 minutes)
    // Thursday 17:00 to 18:00 consumes 1 hour (60 mins)
    // Friday & Saturday are weekend
    // Sunday 09:00 + remaining 1 hour = Sunday 10:00 AM
    $deadline = SlaCalculatorService::calculateDeadline('critical', $thursday5pm);

    expect($deadline->toDateTimeString())->toBe('2026-03-08 10:00:00')
        ->and($deadline->englishDayOfWeek)->toBe('Sunday');
});

test('high priority ticket created during business hours stays on same day if within business window', function () {
    // Monday 2026-03-02 at 09:30 AM
    $mondayMorning = Carbon::parse('2026-03-02 09:30:00');

    // High SLA = 8 hours (480 minutes)
    // 09:30 + 8h = 17:30 (5:30 PM), which is before 18:00
    $deadline = SlaCalculatorService::calculateDeadline('high', $mondayMorning);

    expect($deadline->toDateTimeString())->toBe('2026-03-02 17:30:00')
        ->and($deadline->englishDayOfWeek)->toBe('Monday');
});

test('ticket created during weekend rolls over to start of next business day', function () {
    // Friday 2026-03-06 at 14:00 (Friday is weekend in Sun-Thu calendar)
    $fridayAfternoon = Carbon::parse('2026-03-06 14:00:00');
    expect($fridayAfternoon->englishDayOfWeek)->toBe('Friday');

    // Critical SLA = 2 hours
    // Should start Sunday at 09:00 AM + 2 hours = Sunday at 11:00 AM
    $deadline = SlaCalculatorService::calculateDeadline('critical', $fridayAfternoon);

    expect($deadline->toDateTimeString())->toBe('2026-03-08 11:00:00')
        ->and($deadline->englishDayOfWeek)->toBe('Sunday');
});

test('medium priority ticket spans across multiple business days correctly', function () {
    // Sunday 2026-03-01 at 09:00 AM
    $sundayMorning = Carbon::parse('2026-03-01 09:00:00');

    // Medium SLA = 24 business hours
    // Sunday: 9h (09:00 - 18:00) -> 15h remaining
    // Monday: 9h (09:00 - 18:00) -> 6h remaining
    // Tuesday: 6h (09:00 + 6h) -> Tuesday at 15:00 (3:00 PM)
    $deadline = SlaCalculatorService::calculateDeadline('medium', $sundayMorning);

    expect($deadline->toDateTimeString())->toBe('2026-03-03 15:00:00')
        ->and($deadline->englishDayOfWeek)->toBe('Tuesday');
});
