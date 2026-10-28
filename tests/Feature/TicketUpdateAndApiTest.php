<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authorized agent can update ticket details and changing priority recalculates SLA', function () {
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
    $category1 = Category::create(['name' => 'Billing', 'slug' => 'billing']);
    $category2 = Category::create(['name' => 'Technical', 'slug' => 'technical']);

    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000001',
        'subject' => 'Initial Subject',
        'description' => 'Initial Description',
        'category_id' => $category1->id,
        'priority' => 'low',
        'status' => Ticket::STATUS_OPEN,
        'created_by' => $agent->id,
        'sla_due_at' => now()->addDays(5),
    ]);

    $initialDueAt = $ticket->sla_due_at;

    $response = $this->actingAs($agent)->put(route('tickets.update', $ticket), [
        'subject' => 'Updated Subject',
        'description' => 'Updated Description',
        'category_id' => $category2->id,
        'priority' => 'critical',
    ]);

    $response->assertRedirect(route('tickets.show', $ticket));
    $response->assertSessionHas('success');

    $ticket->refresh();
    expect($ticket->subject)->toBe('Updated Subject')
        ->and($ticket->priority)->toBe('critical')
        ->and($ticket->category_id)->toBe($category2->id)
        ->and($ticket->sla_due_at->ne($initialDueAt))->toBeTrue();
});

test('authenticated user can mark all notifications as read', function () {
    $user = User::factory()->create();
    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000002',
        'subject' => 'Notification Ticket',
        'description' => 'Desc',
        'category_id' => Category::create(['name' => 'General', 'slug' => 'general'])->id,
        'priority' => 'medium',
        'status' => Ticket::STATUS_OPEN,
        'created_by' => $user->id,
    ]);

    $user->notify(new TicketCreatedNotification($ticket));
    expect($user->unreadNotifications()->count())->toBe(1);

    $response = $this->actingAs($user)->post(route('notifications.mark-all-read'));
    $response->assertSessionHas('success');

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('rest api lists tickets and creates a new ticket via json', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $category = Category::create(['name' => 'Security', 'slug' => 'security']);

    $headers = [
        'PHP_AUTH_USER' => $user->email,
        'PHP_AUTH_PW' => 'password',
        'Accept' => 'application/json',
    ];

    // 1. Create ticket via API
    $createResponse = $this->withHeaders($headers)->postJson('/api/v1/tickets', [
        'subject' => 'API Created Ticket',
        'description' => 'Detailed issue reported via API client',
        'category_id' => $category->id,
        'priority' => 'high',
    ]);

    $createResponse->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.priority', 'high');

    $ticketId = $createResponse->json('data.id');

    // 2. Fetch ticket details
    $showResponse = $this->withHeaders($headers)->getJson("/api/v1/tickets/{$ticketId}");
    $showResponse->assertStatus(200)
        ->assertJsonPath('data.subject', 'API Created Ticket');

    // 3. Post reply via API
    $replyResponse = $this->withHeaders($headers)->postJson("/api/v1/tickets/{$ticketId}/reply", [
        'message' => 'Agent response posted via API',
    ]);
    $replyResponse->assertStatus(201)
        ->assertJsonPath('success', true);

    // 4. Resolve ticket via API
    $resolveResponse = $this->withHeaders($headers)->postJson("/api/v1/tickets/{$ticketId}/resolve");
    $resolveResponse->assertStatus(200)
        ->assertJsonPath('data.status', 'resolved');
});
