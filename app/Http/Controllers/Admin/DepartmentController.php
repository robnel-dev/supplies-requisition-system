<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Models\Department;
use App\Http\Services\DepartmentService;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentService $departmentService) {}

    public function index()
    {
        // Using withCount('users') creates a single, highly optimized 
        // SQL query that attaches a 'users_count' attribute to each department.
        $departments = Department::withCount('users')->orderBy('name')->get();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments
        ]);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $this->departmentService->createDepartment($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }
}
