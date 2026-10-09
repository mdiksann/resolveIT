<?php

use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('description');
            $table->string('status', 16)->default(TicketStatus::Open->value)->index();
            // Keep the required requester; deleting an account must not orphan its tickets.
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('priority_id')->constrained()->restrictOnDelete();
            $table->timestamp('due_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('requester_id');
            $table->index('assignee_id');
            $table->index('category_id');
            $table->index('priority_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
