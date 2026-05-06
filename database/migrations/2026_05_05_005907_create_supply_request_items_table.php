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
        Schema::create('supply_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supply_id')->nullable()->constrained('supplies'); // Link to original supply
            $table->string('item_code'); // Snapshot of data in case supply is deleted or disactivated
            $table->string('item_description'); // Snapshot of data
            $table->string('item_unit'); // Snapshot of data
            $table->integer('quantity'); // Current requested quantity
            $table->integer('original_quantity')->nullable(); // Saved at submission
            $table->string('budget_type')->nullable(); // budgeted, unbudgeted (HR use)

            $table->integer('allocated_quantity')->nullable();
            $table->integer('balance')->nullable(); // Calculated as allocated - original (after HR allocation)
            $table->timestamps();

            // Prevent duplicate items in the same cart
            $table->unique(['supply_request_id', 'supply_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_request_items');
    }
};
