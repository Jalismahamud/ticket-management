<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketReply;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'department' => 'Administration',
            ]
        );

        $agent1 = User::firstOrCreate(
            ['email' => 'agent1@example.com'],
            [
                'name' => 'Alex Johnson',
                'password' => Hash::make('password'),
                'role' => User::ROLE_AGENT,
                'is_active' => true,
                'department' => 'Technical Support',
            ]
        );

        $agent2 = User::firstOrCreate(
            ['email' => 'agent2@example.com'],
            [
                'name' => 'Sarah Connor',
                'password' => Hash::make('password'),
                'role' => User::ROLE_AGENT,
                'is_active' => true,
                'department' => 'Billing & Accounts',
            ]
        );

        $customer1 = User::firstOrCreate(
            ['email' => 'customer1@example.com'],
            [
                'name' => 'Michael Scott',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
            ]
        );

        $customer2 = User::firstOrCreate(
            ['email' => 'customer2@example.com'],
            [
                'name' => 'Pam Beesly',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
            ]
        );

        // 2. Categories
        $categories = [
            ['name' => 'Technical Support', 'slug' => 'technical-support', 'description' => 'Software bugs, technical errors, and platform integration issues.'],
            ['name' => 'Billing & Invoicing', 'slug' => 'billing-invoicing', 'description' => 'Subscription charges, invoice requests, and refund queries.'],
            ['name' => 'Account & Access', 'slug' => 'account-access', 'description' => 'Password reset, 2FA setup, and account permissions.'],
            ['name' => 'Feature Request', 'slug' => 'feature-request', 'description' => 'Suggestions and enhancements for upcoming product releases.'],
            ['name' => 'General Inquiry', 'slug' => 'general-inquiry', 'description' => 'General questions regarding our services.'],
        ];

        $createdCategories = [];
        foreach ($categories as $cat) {
            $createdCategories[$cat['slug']] = Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 3. Demo Tickets
        $ticket1 = Ticket::firstOrCreate(
            ['ticket_number' => 'TKT-2026-000001'],
            [
                'subject' => 'Cannot connect to payment gateway API',
                'description' => 'We are receiving HTTP 500 error whenever our checkout calls the payment endpoint.',
                'category_id' => $createdCategories['technical-support']->id,
                'priority' => Ticket::PRIORITY_CRITICAL,
                'status' => Ticket::STATUS_IN_PROGRESS,
                'created_by' => $customer1->id,
                'assigned_to' => $agent1->id,
                'sla_due_at' => Carbon::now()->addHours(2),
                'first_response_at' => Carbon::now()->subMinutes(30),
                'last_reply_at' => Carbon::now()->subMinutes(10),
            ]
        );

        TicketReply::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $agent1->id,
            'message' => 'Hello Michael, we are investigating the gateway logs right now.',
            'is_internal' => false,
        ]);

        TicketReply::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $agent1->id,
            'message' => 'Internal Note: Upstream webhook endpoint timed out. Escalated to DevOps team.',
            'is_internal' => true,
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $customer1->id,
            'type' => TicketActivity::TYPE_CREATED,
            'description' => 'Ticket created by Michael Scott',
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $admin->id,
            'type' => TicketActivity::TYPE_ASSIGNED,
            'description' => 'Ticket assigned to Alex Johnson',
        ]);

        // Demo Ticket 2: Approaching SLA
        $ticket2 = Ticket::firstOrCreate(
            ['ticket_number' => 'TKT-2026-000002'],
            [
                'subject' => 'Invoice discrepancy for March 2026',
                'description' => 'Our monthly billing invoice shows duplicate charge for the premium add-on.',
                'category_id' => $createdCategories['billing-invoicing']->id,
                'priority' => Ticket::PRIORITY_HIGH,
                'status' => Ticket::STATUS_OPEN,
                'created_by' => $customer2->id,
                'assigned_to' => null, // Unassigned
                'sla_due_at' => Carbon::now()->addMinutes(45), // Approaching breach
                'last_reply_at' => Carbon::now()->subHours(2),
            ]
        );

        TicketActivity::create([
            'ticket_id' => $ticket2->id,
            'user_id' => $customer2->id,
            'type' => TicketActivity::TYPE_CREATED,
            'description' => 'Ticket created by Pam Beesly',
        ]);

        // Demo Ticket 3: SLA Breached
        $ticket3 = Ticket::firstOrCreate(
            ['ticket_number' => 'TKT-2026-000003'],
            [
                'subject' => 'Two-factor authentication lock out',
                'description' => 'Lost access to authenticator app on new phone. Need manual recovery.',
                'category_id' => $createdCategories['account-access']->id,
                'priority' => Ticket::PRIORITY_MEDIUM,
                'status' => Ticket::STATUS_IN_PROGRESS,
                'created_by' => $customer1->id,
                'assigned_to' => $agent2->id,
                'sla_due_at' => Carbon::now()->subHours(3), // Breached
                'sla_breached_at' => Carbon::now()->subHours(3),
                'is_escalated' => true,
                'escalated_at' => Carbon::now()->subHours(2),
            ]
        );

        TicketActivity::create([
            'ticket_id' => $ticket3->id,
            'user_id' => $customer1->id,
            'type' => TicketActivity::TYPE_CREATED,
            'description' => 'Ticket created by Michael Scott',
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket3->id,
            'user_id' => null,
            'type' => TicketActivity::TYPE_ESCALATED,
            'description' => 'Ticket escalated due to SLA breach',
        ]);

        // Demo Ticket 4: Resolved
        Ticket::firstOrCreate(
            ['ticket_number' => 'TKT-2026-000004'],
            [
                'subject' => 'How to export ticket history to CSV?',
                'description' => 'Is there an option to export our monthly activity to excel or csv?',
                'category_id' => $createdCategories['general-inquiry']->id,
                'priority' => Ticket::PRIORITY_LOW,
                'status' => Ticket::STATUS_RESOLVED,
                'created_by' => $customer2->id,
                'assigned_to' => $agent1->id,
                'sla_due_at' => Carbon::now()->addHours(36),
                'resolved_at' => Carbon::now()->subHours(5),
            ]
        );
    }
}
