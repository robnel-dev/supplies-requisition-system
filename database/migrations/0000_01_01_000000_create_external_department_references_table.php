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
        Schema::create('external_department_references', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 50)->nullable();
            $table->string('company_code', 10);
            $table->string('department_code', 20);
            $table->string('name');
            $table->string('cost_center', 20);
            $table->enum('branch', ['Head Office', 'Store']);
            $table->string('area', 20)->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('external_id', 'ext_dept_refs_external_id_unique');
            $table->unique(['company_code', 'department_code'], 'ext_dept_refs_company_dept_unique');
            $table->index('branch', 'ext_dept_refs_branch_index');
            $table->index('area', 'ext_dept_refs_area_index');
            $table->index('cost_center', 'ext_dept_refs_cost_center_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_department_references');
    }
};
