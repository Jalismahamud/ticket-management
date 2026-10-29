<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketRepliedNotification;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function makeTicket(array $attributes = []): Ticket
{
    static $counter = 1;
    $category = Category::firstOrCreate(['slug' => 'tech-support'], ['name' => 'Technical Support']);
    $user = User::where('role', User::ROLE_CUSTOMER)->first() ?? User::factory()->create(['role' => User::ROLE_CUSTOMER]);

    $ticketNumber = sprintf('TKT-2026-%06d', $counter++);

    return Ticket::create(array_merge([
        'ticket_number' => $ticketNumber,
        'subject' => 'Sample Ticket ' . $ticketNumber,
        'description' => 'Detailed description of ticket issue.',
        'category_id' => $category->id,
        'priority' => 'medium',
        'status' => Ticket::STATUS_OPEN,
        'created_by' => $user->id,
        'sla_due_at' => now()->addHours(24),
    ], $attributes));
}

test('dispatches TicketCreatedNotification to ticket creator upon creation', function () {
    Notification::fake();

    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $category = Category::create(['name' => 'Support', 'slug' => 'support']);

    $response = $this->actingAs($customer)->post(route('tickets.store'), [
        'subject' => 'Issue with login',
        'category_id' => $category->id,
        'priority' => 'high',
        'description' => 'Cannot log into the account',
    ]);

    $response->assertSessionHasNoErrors();
    $ticket = Ticket::where('subject', 'Issue with login')->firstOrFail();

    Notification::assertSentTo($customer, TicketCreatedNotification::class, function ($notification) use ($ticket) {
        return $notification->ticket->id === $ticket->id;
    });
});

test('dispatches TicketAssignedNotification when ticket is assigned to an agent', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
    $ticket = makeTicket(['assigned_to' => null]);

    $response = $this->actingAs($admin)->post(route('tickets.assign', $ticket), [
        'agent_id' => $agent->id,
    ]);

    $response->assertSessionHasNoErrors();
    expect($ticket->fresh()->assigned_to)->toBe($agent->id);

    Notification::assertSentTo($agent, TicketAssignedNotification::class, function ($notification) use ($ticket) {
        return $notification->ticket->id === $ticket->id;
    });
});

test('dispatches TicketRepliedNotification to customer when agent replies', function () {
    Notification::fake();

    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
    $ticket = makeTicket([
        'created_by' => $customer->id,
        'assigned_to' => $agent->id,
    ]);

    $response = $this->actingAs($agent)->post(route('tickets.reply', $ticket), [
        'message' => 'We are looking into your request now.',
        'is_internal' => false,
    ]);

    $response->assertSessionHasNoErrors();

    Notification::assertSentTo($customer, TicketRepliedNotification::class, function ($notification) use ($ticket) {
        return $notification->ticket->id === $ticket->id;
    });
});

test('dispatches TicketRepliedNotification to agent when customer replies', function () {
    Notification::fake();

    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
    $ticket = makeTicket([
        'created_by' => $customer->id,
        'assigned_to' => $agent->id,
    ]);

    $response = $this->actingAs($customer)->post(route('tickets.reply', $ticket), [
        'message' => 'Here is additional context.',
        'is_internal' => false,
    ]);

    $response->assertSessionHasNoErrors();

    Notification::assertSentTo($agent, TicketRepliedNotification::class, function ($notification) use ($ticket) {
        return $notification->ticket->id === $ticket->id;
    });
});

test('does NOT dispatch reply notification for internal agent notes', function () {
    Notification::fake();

    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
    $ticket = makeTicket([
        'created_by' => $customer->id,
        'assigned_to' => $agent->id,
    ]);

    $response = $this->actingAs($agent)->post(route('tickets.reply', $ticket), [
        'message' => 'Internal debugging note for developers only.',
        'is_internal' => true,
    ]);

    $response->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

test('dispatches TicketResolvedNotification to creator when ticket is resolved', function () {
    Notification::fake();

    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
    $ticket = makeTicket([
        'created_by' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => Ticket::STATUS_IN_PROGRESS,
    ]);

    $response = $this->actingAs($agent)->post(route('tickets.resolve', $ticket));

    $response->assertSessionHasNoErrors();
    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_RESOLVED);

    Notification::assertSentTo($customer, TicketResolvedNotification::class, function ($notification) use ($ticket) {
        return $notification->ticket->id === $ticket->id;
    });
});

test('dispatches notification to active admins when ticket is created unassigned', function () {
    Notification::fake();

    $adminA = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $adminB = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $category = Category::create(['name' => 'General Inquiry', 'slug' => 'general-inquiry']);

    $response = $this->actingAs($adminA)->post(route('tickets.store'), [
        'subject' => 'Unassigned inquiry',
        'category_id' => $category->id,
        'priority' => 'low',
        'description' => 'Need help with account details',
        'auto_assign' => false,
    ]);

    $response->assertSessionHasNoErrors();
    $ticket = Ticket::where('subject', 'Unassigned inquiry')->firstOrFail();

    Notification::assertSentTo($adminA, TicketCreatedNotification::class);
    Notification::assertSentTo($adminB, TicketCreatedNotification::class);
});

test('dispatches notification to customer when agent claims ticket', function () {
    Notification::fake();

    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
    $ticket = makeTicket(['created_by' => $customer->id, 'assigned_to' => null]);

    $response = $this->actingAs($agent)->post(route('tickets.claim', $ticket));

    $response->assertSessionHasNoErrors();
    expect($ticket->fresh()->assigned_to)->toBe($agent->id);

    Notification::assertSentTo($customer, TicketAssignedNotification::class, function ($n) use ($ticket) {
        return $n->ticket->id === $ticket->id;
    });
});

test('dispatches notification to customer when ticket is closed', function () {
    Notification::fake();

    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
    $ticket = makeTicket([
        'created_by' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => Ticket::STATUS_IN_PROGRESS,
    ]);

    $response = $this->actingAs($agent)->post(route('tickets.close', $ticket));

    $response->assertSessionHasNoErrors();
    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_CLOSED);

    Notification::assertSentTo($customer, TicketResolvedNotification::class, function ($n) use ($ticket) {
        return $n->ticket->id === $ticket->id && $n->action === 'Closed';
    });
});

test('dispatches notification when ticket is reopened', function () {
    Notification::fake();

    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);
    $ticket = makeTicket([
        'created_by' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => Ticket::STATUS_RESOLVED,
    ]);

    // Customer reopens ticket
    $response = $this->actingAs($customer)->post(route('tickets.reopen', $ticket));

    $response->assertSessionHasNoErrors();
    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_OPEN);

    Notification::assertSentTo($agent, TicketResolvedNotification::class, function ($n) use ($ticket) {
        return $n->ticket->id === $ticket->id && $n->action === 'Reopened';
    });
});

test('advanced search filters by agent, customer, and date range', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customerA = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'name' => 'Alice']);
    $customerB = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'name' => 'Bob']);
    $agent = User::factory()->create(['role' => User::ROLE_AGENT]);

    $ticketA = makeTicket([
        'created_by' => $customerA->id,
        'assigned_to' => $agent->id,
        'subject' => 'Ticket For Alice',
    ]);
    \Illuminate\Support\Facades\DB::table('tickets')->where('id', $ticketA->id)->update([
        'created_at' => now()->subDays(5)->toDateTimeString(),
    ]);

    $ticketB = makeTicket([
        'created_by' => $customerB->id,
        'assigned_to' => null,
        'subject' => 'Ticket For Bob',
    ]);

    // Filter by assigned agent
    $response = $this->actingAs($admin)->get(route('tickets.index', ['assigned_to' => $agent->id]));
    $response->assertOk();
    $response->assertSee('Ticket For Alice');
    $response->assertDontSee('Ticket For Bob');

    // Filter by customer
    $response = $this->actingAs($admin)->get(route('tickets.index', ['customer_id' => $customerB->id]));
    $response->assertOk();
    $response->assertSee('Ticket For Bob');
    $response->assertDontSee('Ticket For Alice');

    // Filter by created date range
    $response = $this->actingAs($admin)->get(route('tickets.index', [
        'date_from' => now()->subDays(6)->toDateString(),
        'date_to' => now()->subDays(4)->toDateString(),
    ]));
    $response->assertOk();
    $response->assertSee('Ticket For Alice');
    $response->assertDontSee('Ticket For Bob');
});
