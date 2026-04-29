<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        // If this actually connects to a different DB later, uncomment this:
        // protected $connection = 'external_mysql';

        Schema::create('external_supply_references', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique(); // Indexed for fast lookups
            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->integer('available_stocks')->default(0);
            $table->integer('allocatable_stocks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_supply_references');
    }
};
