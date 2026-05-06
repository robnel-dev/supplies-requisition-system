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
        Schema::create('supply_requests', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable()->unique(); // Generated only upon submission
            $table->foreignId('user_id')->constrained()->noActionOnDelete();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('approver_id')->nullable()->constrained('users'); // Assigned approver
            $table->string('status')->default('draft'); // draft, pending_approval, approved, etc.
            $table->timestamp('request_date')->nullable(); // Date when request is submitted (not draft creation)
            $table->string('m3_ro_number')->nullable();
            $table->string('m3_dr_number')->nullable();

            // Approval / Release Tracking
            $table->foreignId('manager_approved_by')->nullable()->constrained('users');
            $table->timestamp('manager_approved_at')->nullable();
            $table->text('manager_notes')->nullable();

            $table->foreignId('hr_admin_released_by')->nullable()->constrained('users');
            $table->timestamp('hr_admin_released_at')->nullable();
            $table->text('hr_admin_notes')->nullable();

            $table->timestamps();

            // Indexes for faster querying
            $table->index(['user_id', 'status', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_requests');
    }
};
