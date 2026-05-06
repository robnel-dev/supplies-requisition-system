<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // //  added ->connection('external_mysql') -referencing the connection to external table from 236 server
        // Schema::connection('external_mysql')->create('external_supply_references', function (Blueprint $table) {
        //     $table->id();

        //     // System & Location Identifiers
        //     $table->string('company_no', 20)->nullable();         // MBCONO
        //     $table->string('warehouse_location', 50)->nullable(); // MBWHLO

        //     // Core Item Details
        //     $table->string('item_code', 50)->unique();              // MBITNO
        //     $table->string('item_description', 255)->nullable();  // MMITDS
        //     $table->string('unit_of_measure', 50)->nullable();    // MMUNMS

        //     // Stock Quantities
        //     $table->integer('stock_quantity')->default(0);        // MBSTQT
        //     $table->integer('allocated_quantity')->default(0);    // MBALQT
        //     $table->integer('allocatable_quantity')->default(0);  // ALLOCATABLE

        //     // Extra / Free fields
        //     $table->string('MBORTY', 50)->nullable();        // MBORTY
        //     $table->string('MBSTAT', 50)->nullable();        // MBSTAT

        //     $table->timestamps();
        // });
    }

    public function down(): void
    {
        // Schema::connection('external_mysql')->dropIfExists('external_supply_references');
    }
};
