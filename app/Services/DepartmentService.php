<?php

namespace App\Services;

use App\Models\Department;
use App\Models\ExternalDepartmentReference;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public const STORE_AREAS = [
        'Area1' => ['code' => 'Area 1', 'name' => 'South Luzon'],
        'Area2' => ['code' => 'Area 2', 'name' => 'North Luzon'],
        'Area3' => ['code' => 'Area 3', 'name' => 'NCR 1'],
        'Area4' => ['code' => 'Area 4', 'name' => 'NCR 2'],
        'Area5' => ['code' => 'Area 5', 'name' => 'Visayas'],
        'Area6' => ['code' => 'Area 6', 'name' => 'Mindanao'],
    ];

    public function getHeadOfficeRefs(): \Illuminate\Support\Collection
    {
        return ExternalDepartmentReference::active()
            ->headOffice()
            ->orderBy('name')
            ->get(['id', 'company_code', 'department_code', 'name', 'cost_center', 'area'])
            ->unique('department_code')
            ->values();
    }

    public function getStoreAreas(): array
    {
        return array_keys(self::STORE_AREAS);
    }

    public function getStoreAreaOptions(): array
    {
        return collect(self::STORE_AREAS)
            ->map(fn (array $area, string $key) => [
                'area' => $key,
                'code' => $area['code'],
                'name' => $area['name'],
            ])
            ->values()
            ->all();
    }

    public function getStoreRefsByArea(string $area): \Illuminate\Support\Collection
    {
        return ExternalDepartmentReference::active()
            ->store()
            ->where('area', $area)
            ->whereIn('area', array_keys(self::STORE_AREAS))
            ->orderBy('name')
            ->get(['id', 'company_code', 'department_code', 'name', 'cost_center', 'area']);
    }

    public function create(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            return Department::create($this->attributesFromRequest($data));
        });
    }

    public function update(Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            $department->update($this->attributesFromRequest($data));

            return $department->fresh();
        });
    }

    public function delete(Department $department): void
    {
        if ($department->users()->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'Cannot delete this department because users are assigned to it.',
            ]);
        }

        $department->delete();
    }

    private function attributesFromRequest(array $data): array
    {
        $reference = $data['type'] === 'head_office'
            ? $this->resolveExternalReference($data['external_department_reference_id'] ?? null)
            : null;

        $areaKey = $data['area'] ?? null;
        $area = $areaKey ? (self::STORE_AREAS[$areaKey] ?? null) : null;
        $costCenter = filled($data['cost_center'] ?? null)
            ? trim((string) $data['cost_center'])
            : null;

        return [
            'external_department_reference_id' => $reference?->id,
            'code' => $data['type'] === 'store'
                ? $area['code']
                : ($reference?->department_code ?? trim((string) $data['code'])),
            'name' => $data['type'] === 'store'
                ? $area['name']
                : ($reference?->name ?? trim((string) $data['name'])),
            'type' => $data['type'],
            'area' => $data['type'] === 'store' ? $areaKey : ($reference?->area ?? ($data['area'] ?? null)),
            'cost_center' => $costCenter,
            'cost_center_source' => $reference && $costCenter === $reference->cost_center
                ? 'external'
                : 'manual',
            'is_custom' => $data['type'] === 'store' || $reference === null,
        ];
    }

    private function resolveExternalReference(null|int|string $referenceId): ?ExternalDepartmentReference
    {
        if (! $referenceId) {
            return null;
        }

        return ExternalDepartmentReference::active()->headOffice()->findOrFail($referenceId);
    }
}
