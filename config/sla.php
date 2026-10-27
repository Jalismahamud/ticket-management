<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SLA Resolution Time per Priority (in hours)
    |--------------------------------------------------------------------------
    */
    'priorities' => [
        'critical' => 2,   // 2 Hours
        'high'     => 8,   // 8 Hours
        'medium'   => 24,  // 24 Hours
        'low'      => 48,  // 48 Hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Hours Configuration
    |--------------------------------------------------------------------------
    | Days of week (Carbon: 0 = Sunday, 1 = Monday, ..., 6 = Saturday)
    | Default: Sunday - Thursday (Saudi/Middle East or configurable enterprise work week)
    */
    'business_days' => [
        \Carbon\CarbonInterface::SUNDAY,
        \Carbon\CarbonInterface::MONDAY,
        \Carbon\CarbonInterface::TUESDAY,
        \Carbon\CarbonInterface::WEDNESDAY,
        \Carbon\CarbonInterface::THURSDAY,
    ],

    'business_hours' => [
        'start' => '09:00',
        'end'   => '18:00',
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA Warning & Escalation Thresholds
    |--------------------------------------------------------------------------
    | Time before SLA breach to mark ticket as 'approaching' (in minutes)
    */
    'approaching_warning_minutes' => 120, // 2 Hours

    /*
    |--------------------------------------------------------------------------
    | Ticket ID Generation Prefix
    |--------------------------------------------------------------------------
    */
    'ticket_id_prefix' => 'TKT',
];
