<?php

use App\Exceptions\TicketAlreadyAssignedException;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('support agent can claim an unassigned ticket successfully', function () {
    $category = Category::create(['name' => 'General Support', 'slug' => 'general-support']);
    $customer = User::create([
        'name' => 'Customer John',
        'email' => 'john@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
    ]);
    $agent = User::create([
        'name' => 'Agent Alex',
        'email' => 'alex@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_AGENT,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000001',
        'subject' => 'Unassigned issue',
        'description' => 'Need help',
        'category_id' => $category->id,
        'created_by' => $customer->id,
        'priority' => 'medium',
        'status' => Ticket::STATUS_OPEN,
        'assigned_to' => null,
    ]);

    $service = new TicketAssignmentService();
    $service->claim($ticket, $agent);

    expect($ticket->fresh()->assigned_to)->toBe($agent->id)
        ->and($ticket->fresh()->status)->toBe(Ticket::STATUS_IN_PROGRESS);
});

test('concurrent claim on already assigned ticket throws TicketAlreadyAssignedException and preserves integrity', function () {
    $category = Category::create(['name' => 'Technical', 'slug' => 'technical']);
    $customer = User::create([
        'name' => 'Customer Pam',
        'email' => 'pam@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
    ]);
    $agent1 = User::create([
        'name' => 'Agent Alex',
        'email' => 'alex@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_AGENT,
    ]);
    $agent2 = User::create([
        'name' => 'Agent Sarah',
        'email' => 'sarah@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_AGENT,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000002',
        'subject' => 'High priority issue',
        'description' => 'Checkout error',
        'category_id' => $category->id,
        'created_by' => $customer->id,
        'priority' => 'high',
        'status' => Ticket::STATUS_OPEN,
        'assigned_to' => null,
    ]);

    $service = new TicketAssignmentService();

    // Agent 1 claims first
    $service->claim($ticket, $agent1);
    expect($ticket->fresh()->assigned_to)->toBe($agent1->id);

    // Agent 2 attempts to claim the same ticket concurrently
    expect(fn() => $service->claim($ticket->fresh(), $agent2))
        ->toThrow(TicketAlreadyAssignedException::class);

    // Ensure the ticket is still cleanly assigned to Agent 1 and not corrupted
    expect($ticket->fresh()->assigned_to)->toBe($agent1->id);
});
