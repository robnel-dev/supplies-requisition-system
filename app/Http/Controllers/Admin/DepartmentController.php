<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentService $departmentService) {}

    public function index()
    {
        // Authorize an action
        Gate::authorize('viewAny', Department::class);

        $departments = Department::withCount('users')->orderBy('name')->get();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments
        ]);
    }

    public function store(StoreDepartmentRequest $request)
    {
        Gate::authorize('create', Department::class);

        $this->departmentService->createDepartment($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        Gate::authorize('update', $department);

        $this->departmentService->updateDepartment($department, $request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        Gate::authorize('delete', $department);

        try {
            $this->departmentService->deleteDepartment($department);
            return redirect()->route('admin.departments.index')
                ->with('success', 'Department deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['delete' => $e->getMessage()]);
        }
    }
}
