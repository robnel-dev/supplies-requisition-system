<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_request_id')
                ->constrained('supply_requests')
                ->cascadeOnDelete();

            // Short machine-readable action label (e.g. 'submitted', 'approved', 'rejected')
            $table->string('action');

            // Human-readable sentence shown in the timeline UI
            $table->text('description');

            // Who performed the action (null = system)
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['supply_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_timelines');
    }
};
