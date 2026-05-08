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
            $table->foreignId('external_department_reference_id')
                ->nullable()
                ->constrained('external_department_references')
                ->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('type', ['head_office', 'store'])->default('head_office');
            $table->string('area', 20)->nullable();
            $table->string('cost_center', 20)->nullable();
            $table->enum('cost_center_source', ['external', 'manual'])->default('manual');
            $table->boolean('is_custom')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
