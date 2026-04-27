<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Human Resources", "SM Bacolod Adult"
            $table->string('code')->unique(); // e.g., "HRD", "Area 1"
            $table->enum('type', ['head_office', 'store'])->default('head_office');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};