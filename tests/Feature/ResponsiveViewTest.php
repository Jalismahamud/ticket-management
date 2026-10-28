<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('all critical portal views render successfully across authenticated user roles', function () {
    $category = Category::create(['name' => 'General Inquiry', 'slug' => 'general-inquiry']);
    $admin = User::create([
        'name' => 'Admin Manager',
        'email' => 'admin.manager@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000501',
        'subject' => 'Responsive test ticket with very long subject header to verify no viewport breaking issues',
        'description' => 'Test description with details.',
        'category_id' => $category->id,
        'created_by' => $admin->id,
        'assigned_to' => $admin->id,
        'priority' => 'high',
        'status' => Ticket::STATUS_IN_PROGRESS,
    ]);

    // 1. Dashboard View
    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('SLA Dashboard', false)
        ->assertSee('Active Tickets by Priority');

    // 2. Tickets Index View
    $this->actingAs($admin)->get(route('tickets.index'))
        ->assertOk()
        ->assertSee('All Tickets')
        ->assertSee('TKT-2026-000501');

    // 3. Ticket Show View
    $this->actingAs($admin)->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('TKT-2026-000501')
        ->assertSee('Reply to Customer');

    // 4. Ticket Create View
    $this->actingAs($admin)->get(route('tickets.create'))
        ->assertOk()
        ->assertSee('Create New Support Ticket');

    // 5. Ticket Edit View
    $this->actingAs($admin)->get(route('tickets.edit', $ticket))
        ->assertOk()
        ->assertSee('Edit Ticket');

    // 6. CSV Import View
    $this->actingAs($admin)->get(route('tickets.import'))
        ->assertOk()
        ->assertSee('Bulk CSV Ticket Import');

    // 7. Profile View
    $this->actingAs($admin)->get(route('profile'))
        ->assertOk()
        ->assertSee('Personal Information');

    // 8. Settings View
    $this->actingAs($admin)->get(route('settings.index'))
        ->assertOk()
        ->assertSee('Business Hours', false);

    // 9. Dedicated Print View
    $this->actingAs($admin)->get(route('tickets.print', $ticket))
        ->assertOk()
        ->assertSee('TKT-2026-000501');
});
