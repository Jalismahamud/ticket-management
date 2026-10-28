<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('authenticated user can view profile page with stats', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => User::ROLE_AGENT,
    ]);

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertOk()
        ->assertSee('John Doe')
        ->assertSee('john@example.com');
});

test('authenticated user can update profile name, email and department', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'department' => 'Support',
    ]);

    $response = $this->actingAs($user)->post(route('profile.update'), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'department' => 'Tier 2 Support',
    ]);

    $response->assertRedirect(route('profile'))
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('updated@example.com')
        ->and($user->department)->toBe('Tier 2 Support');
});

test('authenticated user can update password securely', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-secret-password'),
    ]);

    $response = $this->actingAs($user)->post(route('profile.password'), [
        'current_password' => 'old-secret-password',
        'password' => 'new-secret-password-123',
        'password_confirmation' => 'new-secret-password-123',
    ]);

    $response->assertRedirect(route('profile'))
        ->assertSessionHas('success');

    $user->refresh();
    expect(Hash::check('new-secret-password-123', $user->password))->toBeTrue();
});

test('non-admin user is forbidden from accessing system settings', function () {
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

    $this->actingAs($customer)->get(route('settings.index'))
        ->assertForbidden();

    $this->actingAs($customer)->post(route('settings.update'), [])
        ->assertForbidden();
});

test('admin can update system settings and changes reflect dynamically', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post(route('settings.update'), [
        'app_name' => 'SupportPulse Pro',
        'support_email' => 'desk@supportpulse.local',
        'ticket_prefix' => 'HELP',
        'assignment_strategy' => 'workload',
        'sla_critical' => 4,
        'sla_high' => 12,
        'sla_medium' => 36,
        'sla_low' => 72,
        'sla_warning_minutes' => 60,
        'business_days' => [1, 2, 3, 4, 5], // Mon-Fri
        'business_start' => '08:00',
        'business_end' => '17:00',
    ]);

    $response->assertRedirect(route('settings.index'))
        ->assertSessionHas('success');

    expect(Setting::get('app_name'))->toBe('SupportPulse Pro')
        ->and(Setting::get('ticket_prefix'))->toBe('HELP')
        ->and(Setting::get('assignment_strategy'))->toBe('workload')
        ->and(Setting::get('sla_critical'))->toBe(4)
        ->and(Setting::get('business_start'))->toBe('08:00')
        ->and(Setting::get('business_end'))->toBe('17:00');
});
