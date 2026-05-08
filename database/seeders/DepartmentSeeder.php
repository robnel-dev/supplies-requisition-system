<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ExternalDepartmentReference;
use App\Services\DepartmentService;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $headOfficeReferences = ExternalDepartmentReference::active()
            ->headOffice()
            ->orderBy('name')
            ->get()
            ->unique('department_code');

        foreach ($headOfficeReferences as $reference) {
            Department::updateOrCreate(
                ['code' => $reference->department_code],
                [
                    'external_department_reference_id' => $reference->id,
                    'name' => $reference->name,
                    'type' => 'head_office',
                    'area' => $reference->area ?: 'HO',
                    'cost_center' => $reference->cost_center,
                    'cost_center_source' => 'external',
                    'is_custom' => false,
                ]
            );
        }

        foreach (DepartmentService::STORE_AREAS as $area => $details) {
            Department::updateOrCreate(
                ['code' => $details['code']],
                [
                    'external_department_reference_id' => null,
                    'name' => $details['name'],
                    'type' => 'store',
                    'area' => $area,
                    'cost_center' => null,
                    'cost_center_source' => 'manual',
                    'is_custom' => true,
                ]
            );
        }

        $this->command?->info('Departments seeded: HO functions plus store areas.');
    }
}
