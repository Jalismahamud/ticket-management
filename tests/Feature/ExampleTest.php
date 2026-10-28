<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the signin page returns a successful response', function () {
    $response = $this->get('/signin');

    $response->assertStatus(200);
});

test('authenticated user can view dashboard successfully', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
    $response->assertSee('Support Ticket');
});
