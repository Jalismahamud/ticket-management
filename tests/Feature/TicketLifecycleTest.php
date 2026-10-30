<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('ticket creation automatically generates sequential formatted ticket number and audit activity', function () {
    $category = Category::create(['name' => 'Technical Support', 'slug' => 'technical-support']);
    $customer = User::create([
        'name' => 'Michael Scott',
        'email' => 'michael@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
    ]);

    $response = $this->actingAs($customer)->post(route('tickets.store'), [
        'subject' => 'Cannot print color documents',
        'description' => 'The office printer is flashing an error code 42.',
        'category_id' => $category->id,
        'priority' => 'medium',
    ]);

    $ticket = Ticket::first();
    expect($ticket)->not->toBeNull()
        ->and($ticket->ticket_number)->toMatch('/^TKT-\d{4}-\d{6}$/')
        ->and($ticket->activities()->count())->toBeGreaterThanOrEqual(1);

    $response->assertRedirect(route('tickets.show', $ticket));
});

test('customer reply to ticket in waiting_for_customer transitions status to in_progress', function () {
    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $customer = User::create([
        'name' => 'Dwight Schrute',
        'email' => 'dwight@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000001',
        'subject' => 'Beet delivery delay',
        'description' => 'Tracking stopped.',
        'category_id' => $category->id,
        'created_by' => $customer->id,
        'priority' => 'low',
        'status' => Ticket::STATUS_WAITING_CUSTOMER,
    ]);

    $this->actingAs($customer)->post(route('tickets.reply', $ticket), [
        'message' => 'Here is my tracking invoice number: #12345',
    ]);

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_IN_PROGRESS);
});

test('csv import service parses rows, validates fields, and ignores duplicates', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::create(['name' => 'Billing & Invoicing', 'slug' => 'billing-invoicing']);

    $csvContent = "subject,description,priority,category,customer_email\n";
    $csvContent .= "Invoice Question,Need copy of last invoice,low,Billing & Invoicing,client1@example.com\n";
    $csvContent .= "Duplicate Issue,System issue,high,Billing & Invoicing,client2@example.com\n";
    $csvContent .= "Duplicate Issue,System issue,high,Billing & Invoicing,client2@example.com\n"; // duplicate row

    $tempFile = tmpfile();
    fwrite($tempFile, $csvContent);
    $meta = stream_get_meta_data($tempFile);
    $path = $meta['uri'];

    $uploadedFile = new UploadedFile($path, 'test.csv', 'text/csv', null, true);

    $service = new TicketImportService();
    $result = $service->import($uploadedFile, $admin);

    expect($result['imported'])->toBe(2)
        ->and($result['skipped'])->toBe(1) // Duplicate skipped
        ->and(Ticket::count())->toBe(2);

    fclose($tempFile);
});

test('authorized user can view dedicated printable ticket report', function () {
    $category = Category::create(['name' => 'Hardware', 'slug' => 'hardware']);
    $customer = User::create([
        'name' => 'Jim Halpert',
        'email' => 'jim@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CUSTOMER,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'TKT-2026-000099',
        'subject' => 'Broken mouse',
        'description' => 'The optical sensor is not working.',
        'category_id' => $category->id,
        'created_by' => $customer->id,
        'priority' => 'medium',
        'status' => Ticket::STATUS_OPEN,
    ]);

    $response = $this->actingAs($customer)->get(route('tickets.print', $ticket));
    $response->assertOk();
    $response->assertSee('TKT-2026-000099');
    $response->assertSee('Official Support Incident', false);
});

test('csv import tracks progress via cache and reports live percentage', function () {
    $admin = User::create([
        'name' => 'Dwight Schrute',
        'email' => 'dwight@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::create(['name' => 'Logistics', 'slug' => 'logistics']);

    $csvContent = implode("\n", [
        'subject,description,priority,category,customer_email',
        'Beet Shipment Delayed,Need updates on farm delivery,high,Logistics,farmer1@example.com',
        'Order Receipt Missing,Cannot find invoice in portal,low,Logistics,farmer2@example.com',
    ]);

    $tempFile = tmpfile();
    fwrite($tempFile, $csvContent);
    $meta = stream_get_meta_data($tempFile);
    $path = $meta['uri'];

    $uploadedFile = new \Illuminate\Http\UploadedFile(
        $path,
        'batch.csv',
        'text/csv',
        null,
        true
    );

    $importId = 'test-track-' . uniqid();

    $response = $this->actingAs($admin)
        ->post(route('tickets.import.post'), [
            'csv_file' => $uploadedFile,
            'import_id' => $importId,
        ], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'import_id' => $importId,
        ]);

    // Check live progress endpoint
    $progressResponse = $this->actingAs($admin)->get(route('tickets.import.progress', $importId));
    $progressResponse->assertOk()
        ->assertJson([
            'status' => 'completed',
            'percentage' => 100,
            'total' => 2,
            'imported' => 2,
        ]);

    fclose($tempFile);
});
