<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('customer can view own ticket but cannot access other customer ticket', function () {
    $category = Category::create(['name' => 'Support', 'slug' => 'support']);

    $customerA = User::create([
        'name' => 'Customer A',
        'email' => 'a@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
    ]);

    $customerB = User::create([
        'name' => 'Customer B',
        'email' => 'b@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
    ]);

    $ticketA = Ticket::create([
        'ticket_number' => 'TKT-2026-000001',
        'subject' => 'Ticket A',
        'description' => 'Help A',
        'category_id' => $category->id,
        'created_by' => $customerA->id,
        'priority' => 'medium',
        'status' => Ticket::STATUS_OPEN,
    ]);

    // Customer A can view their own ticket
    $this->actingAs($customerA)
        ->get(route('tickets.show', $ticketA))
        ->assertStatus(200);

    // Customer B cannot view Customer A's ticket (403 Forbidden)
    $this->actingAs($customerB)
        ->get(route('tickets.show', $ticketA))
        ->assertStatus(403);
});

test('internal agent notes are never visible to customers', function () {
    $category = Category::create(['name' => 'Billing', 'slug' => 'billing']);

    $customer = User::create([
        'name' => 'Customer User',
        'email' => 'customer@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
    ]);

    $agent = User::create([
        'name' => 'Agent Smith',
        'email' => 'smith@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_AGENT,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000002',
        'subject' => 'Billing Issue',
        'description' => 'Invoice problem',
        'category_id' => $category->id,
        'created_by' => $customer->id,
        'priority' => 'high',
        'status' => Ticket::STATUS_OPEN,
    ]);

    // Create a public reply and an internal note
    TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'message' => 'PUBLIC_RESPONSE: We are checking this for you.',
        'is_internal' => false,
    ]);

    TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'message' => 'SECRET_INTERNAL_NOTE: Gateway refund failure, do not reveal to client.',
        'is_internal' => true,
    ]);

    // When customer views the ticket:
    $response = $this->actingAs($customer)
        ->get(route('tickets.show', $ticket));

    $response->assertStatus(200);
    $response->assertSee('PUBLIC_RESPONSE: We are checking this for you.');
    $response->assertDontSee('SECRET_INTERNAL_NOTE: Gateway refund failure, do not reveal to client.');

    // When agent views the ticket:
    $agentResponse = $this->actingAs($agent)
        ->get(route('tickets.show', $ticket));

    $agentResponse->assertStatus(200);
    $agentResponse->assertSee('PUBLIC_RESPONSE: We are checking this for you.');
    $agentResponse->assertSee('SECRET_INTERNAL_NOTE: Gateway refund failure, do not reveal to client.');
});

test('customers cannot create internal notes', function () {
    $category = Category::create(['name' => 'Support', 'slug' => 'support']);
    $customer = User::create([
        'name' => 'Customer User',
        'email' => 'customer@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000003',
        'subject' => 'Support Issue',
        'description' => 'Details',
        'category_id' => $category->id,
        'created_by' => $customer->id,
        'priority' => 'low',
        'status' => Ticket::STATUS_OPEN,
    ]);

    // Customer attempts to post with is_internal = 1
    $response = $this->actingAs($customer)
        ->post(route('tickets.reply', $ticket), [
            'message' => 'Hacked internal note',
            'is_internal' => 1,
        ]);

    $response->assertStatus(403);
});
