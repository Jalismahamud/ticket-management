<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 32)->unique();
            $table->string('subject');
            $table->longText('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->index();
            $table->enum('status', ['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed'])->default('open')->index();
            
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            
            // SLA tracking
            $table->timestamp('sla_due_at')->nullable()->index();
            $table->timestamp('sla_breached_at')->nullable()->index();
            $table->boolean('is_escalated')->default(false)->index();
            $table->timestamp('escalated_at')->nullable();
            
            // Lifecycle timestamps
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_reply_at')->nullable()->index();
            
            $table->timestamps();

            // Compound indexes for optimal search and filtering performance
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['status', 'sla_due_at']);
            $table->index(['is_escalated', 'sla_due_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
