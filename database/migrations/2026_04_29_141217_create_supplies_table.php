<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique(); 
            
            $table->string('item_name')->nullable();
            $table->text('item_description')->nullable(); 

            $table->string('category')->index();
            $table->string('unit');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};