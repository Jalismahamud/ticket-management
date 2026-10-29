<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketEscalatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can fetch notifications via json endpoint with unread count', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Support', 'slug' => 'support']);
    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000088',
        'subject' => 'Payment issue',
        'description' => 'Payment issue desc',
        'category_id' => $category->id,
        'created_by' => $user->id,
        'priority' => 'high',
        'status' => 'open',
    ]);

    $user->notify(new TicketAssignedNotification($ticket));

    $response = $this->actingAs($user)->getJson(route('notifications.fetch'));

    $response->assertOk()
        ->assertJsonStructure([
            'unread_count',
            'total_count',
            'notifications' => [
                '*' => ['id', 'type', 'message', 'ticket_id', 'is_unread', 'url'],
            ],
        ])
        ->assertJson([
            'unread_count' => 1,
            'total_count' => 1,
        ]);
});

test('user can mark a single notification as read dynamically via ajax', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Billing', 'slug' => 'billing']);
    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000089',
        'subject' => 'Invoice inquiry',
        'description' => 'Invoice desc',
        'category_id' => $category->id,
        'created_by' => $user->id,
        'priority' => 'medium',
        'status' => 'open',
    ]);

    $user->notify(new TicketEscalatedNotification($ticket, 'SLA Breached'));
    $notification = $user->unreadNotifications()->first();

    $response = $this->actingAs($user)->postJson(route('notifications.mark-read', $notification->id));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'unread_count' => 0,
        ]);

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('user can mark all notifications as read via ajax json request', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Hardware', 'slug' => 'hardware']);
    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000090',
        'subject' => 'Monitor broken',
        'description' => 'Monitor broken desc',
        'category_id' => $category->id,
        'created_by' => $user->id,
        'priority' => 'low',
        'status' => 'open',
    ]);

    $user->notify(new TicketAssignedNotification($ticket));
    $user->notify(new TicketEscalatedNotification($ticket));

    expect($user->unreadNotifications()->count())->toBe(2);

    $response = $this->actingAs($user)->postJson(route('notifications.mark-all-read'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'unread_count' => 0,
        ]);

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('user can delete a notification and clear read notifications', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Cloud', 'slug' => 'cloud']);
    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000091',
        'subject' => 'Server down',
        'description' => 'Server down desc',
        'category_id' => $category->id,
        'created_by' => $user->id,
        'priority' => 'critical',
        'status' => 'open',
    ]);

    $user->notify(new TicketAssignedNotification($ticket));
    $user->notify(new TicketEscalatedNotification($ticket));

    $notification = $user->unreadNotifications()->first();

    // 1. Delete single notification
    $delRes = $this->actingAs($user)->deleteJson(route('notifications.destroy', $notification->id));
    $delRes->assertOk()->assertJson(['success' => true]);
    expect($user->notifications()->count())->toBe(1);

    // 2. Mark remaining read and clear read
    $remaining = $user->unreadNotifications()->first();
    $remaining->markAsRead();

    $clearRes = $this->actingAs($user)->postJson(route('notifications.clear-read'));
    $clearRes->assertOk()->assertJson(['success' => true, 'unread_count' => 0]);
    expect($user->notifications()->count())->toBe(0);
});

