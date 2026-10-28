<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display the system settings configuration.
     */
    public function index(): View
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Unauthorized access to System Settings.');

        $settings = [
            'app_name' => Setting::get('app_name', config('app.name', 'SupportDesk')),
            'support_email' => Setting::get('support_email', 'support@supportdesk.local'),
            'ticket_prefix' => Setting::get('ticket_prefix', config('sla.ticket_id_prefix', 'TKT')),
            'assignment_strategy' => Setting::get('assignment_strategy', 'round_robin'),
            'sla_critical' => (int) Setting::get('sla_critical', config('sla.priorities.critical', 2)),
            'sla_high' => (int) Setting::get('sla_high', config('sla.priorities.high', 8)),
            'sla_medium' => (int) Setting::get('sla_medium', config('sla.priorities.medium', 24)),
            'sla_low' => (int) Setting::get('sla_low', config('sla.priorities.low', 48)),
            'sla_warning_minutes' => (int) Setting::get('sla_warning_minutes', config('sla.approaching_warning_minutes', 120)),
            'business_days' => (array) Setting::get('business_days', [0, 1, 2, 3, 4]),
            'business_start' => (string) Setting::get('business_start', '09:00'),
            'business_end' => (string) Setting::get('business_end', '18:00'),
        ];

        return view('pages.settings.index', compact('settings'));
    }

    /**
     * Update and persist system settings dynamically.
     */
    public function update(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Unauthorized action.');

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'support_email' => ['required', 'email', 'max:255'],
            'ticket_prefix' => ['required', 'string', 'alpha_dash', 'max:10'],
            'assignment_strategy' => ['required', 'in:round_robin,workload,manual'],
            'sla_critical' => ['required', 'integer', 'min:1', 'max:168'],
            'sla_high' => ['required', 'integer', 'min:1', 'max:168'],
            'sla_medium' => ['required', 'integer', 'min:1', 'max:168'],
            'sla_low' => ['required', 'integer', 'min:1', 'max:168'],
            'sla_warning_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'business_days' => ['required', 'array'],
            'business_days.*' => ['integer', 'between:0,6'],
            'business_start' => ['required', 'date_format:H:i'],
            'business_end' => ['required', 'date_format:H:i', 'after:business_start'],
        ]);

        foreach ($validated as $key => $value) {
            $group = match (true) {
                str_starts_with($key, 'sla_') => 'sla',
                str_starts_with($key, 'business_') => 'business_hours',
                default => 'general',
            };

            Setting::set($key, $value, $group);
        }

        return redirect()->route('settings.index')->with('success', 'System settings saved and applied successfully across SLA & Tickets.');
    }
}
