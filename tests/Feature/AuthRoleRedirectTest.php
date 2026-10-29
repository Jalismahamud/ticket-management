<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin login redirects to admin dashboard', function () {
    $admin = User::factory()->create([
        'email' => 'admin.test@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'admin.test@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($admin);
});

test('support agent login redirects to agent dashboard', function () {
    $agent = User::factory()->create([
        'email' => 'agent.test@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_AGENT,
        'is_active' => true,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'agent.test@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($agent);
});

test('customer login redirects to customer tickets portal', function () {
    $customer = User::factory()->create([
        'email' => 'customer.test@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
        'is_active' => true,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'customer.test@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('tickets.index'));
    $this->assertAuthenticatedAs($customer);
});

test('customer registration redirects to customer tickets portal with role customer', function () {
    $response = $this->post(route('register'), [
        'fname' => 'John',
        'lname' => 'Doe',
        'email' => 'johndoe@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('tickets.index'));

    $user = User::where('email', 'johndoe@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe(User::ROLE_CUSTOMER);
    $this->assertAuthenticatedAs($user);
});

test('inactive user is rejected and cannot log in', function () {
    $inactiveUser = User::factory()->create([
        'email' => 'inactive@example.com',
        'password' => bcrypt('password'),
        'is_active' => false,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

