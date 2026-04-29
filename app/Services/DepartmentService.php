<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function createDepartment(array $data): Department
    {
        return Department::create($data);
    }

    public function updateDepartment(Department $department, array $data): bool
    {
        return $department->update($data);
    }

    public function deleteDepartment(Department $department): void
    {
        // Business Logic: Prevent deletion if users are assigned
        if ($department->users()->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'Cannot delete department. There are active users assigned to it.'
            ]);
        }

        $department->delete();
    }
}
