<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('global search with exact ticket number jumps directly to ticket show page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $category = Category::create(['name' => 'IT Support', 'slug' => 'it-support']);

    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000999',
        'subject' => 'Server issue',
        'description' => 'Server unreachable at 192.168.1.1',
        'category_id' => $category->id,
        'created_by' => $admin->id,
        'priority' => 'critical',
        'status' => Ticket::STATUS_OPEN,
    ]);

    $response = $this->actingAs($admin)->get(route('global.search', ['q' => 'TKT-2026-000999']));

    $response->assertRedirect(route('tickets.show', $ticket));
});

test('global search with generic query redirects to tickets index with search filter', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->get(route('global.search', ['q' => 'database error']));

    $response->assertRedirect(route('tickets.index', ['search' => 'database error']));
});

test('global live search returns matching tickets by subject, description phone number, and creator email', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = User::factory()->create([
        'name' => 'Sarah Connor',
        'email' => 'sarah@resistance.com',
        'role' => User::ROLE_CUSTOMER,
    ]);
    $category = Category::create(['name' => 'General', 'slug' => 'general']);

    $ticket1 = Ticket::create([
        'ticket_number' => 'TKT-2026-000801',
        'subject' => 'Billing inquiry',
        'description' => 'Please contact customer on phone +1-555-0199 regarding the invoice.',
        'category_id' => $category->id,
        'created_by' => $customer->id,
        'priority' => 'medium',
        'status' => Ticket::STATUS_OPEN,
    ]);

    // 1. Search by phone number in description
    $response = $this->actingAs($admin)->getJson(route('global.search.live', ['q' => '555-0199']));
    $response->assertOk();
    $data = $response->json();
    expect($data['tickets'])->toHaveCount(1);
    expect($data['tickets'][0]['ticket_number'])->toBe('TKT-2026-000801');

    // 2. Search by customer email
    $response = $this->actingAs($admin)->getJson(route('global.search.live', ['q' => 'resistance.com']));
    $response->assertOk();
    $data = $response->json();
    expect($data['tickets'])->toHaveCount(1);
    expect($data['tickets'][0]['creator'])->toBe('Sarah Connor');
});

test('customer only sees their own tickets in live search', function () {
    $customerA = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $customerB = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $category = Category::create(['name' => 'Tech', 'slug' => 'tech']);

    $ticketA = Ticket::create([
        'ticket_number' => 'TKT-2026-000701',
        'subject' => 'Secret ticket for Customer A',
        'description' => 'Details A',
        'category_id' => $category->id,
        'created_by' => $customerA->id,
        'priority' => 'medium',
        'status' => Ticket::STATUS_OPEN,
    ]);

    $ticketB = Ticket::create([
        'ticket_number' => 'TKT-2026-000702',
        'subject' => 'Secret ticket for Customer B',
        'description' => 'Details B',
        'category_id' => $category->id,
        'created_by' => $customerB->id,
        'priority' => 'medium',
        'status' => Ticket::STATUS_OPEN,
    ]);

    // Customer A searches for "Secret"
    $response = $this->actingAs($customerA)->getJson(route('global.search.live', ['q' => 'Secret']));
    $response->assertOk();
    $data = $response->json();
    expect($data['tickets'])->toHaveCount(1);
    expect($data['tickets'][0]['ticket_number'])->toBe('TKT-2026-000701');
    expect($data['users'])->toHaveCount(0); // Customers cannot browse other user accounts
});

