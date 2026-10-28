<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->timestamps();
        });

        // Insert initial defaults
        $now = now();
        DB::table('settings')->insert([
            ['key' => 'app_name', 'value' => 'SupportDesk', 'group' => 'general', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'support_email', 'value' => 'support@supportdesk.local', 'group' => 'general', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'ticket_prefix', 'value' => 'TKT', 'group' => 'general', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'assignment_strategy', 'value' => 'round_robin', 'group' => 'general', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'sla_critical', 'value' => '2', 'group' => 'sla', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'sla_high', 'value' => '8', 'group' => 'sla', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'sla_medium', 'value' => '24', 'group' => 'sla', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'sla_low', 'value' => '48', 'group' => 'sla', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'sla_warning_minutes', 'value' => '120', 'group' => 'sla', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'business_days', 'value' => json_encode([0, 1, 2, 3, 4]), 'group' => 'business_hours', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'business_start', 'value' => '09:00', 'group' => 'business_hours', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'business_end', 'value' => '18:00', 'group' => 'business_hours', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
