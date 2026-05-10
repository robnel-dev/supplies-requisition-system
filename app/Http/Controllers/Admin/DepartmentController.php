<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Department::class);

        $search = $request->input('search');

        $departments = Department::with('externalReference')
            ->withCount('users')
            ->when($search, function ($query, $search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%")
                    ->orWhere('cost_center', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'totalDepartments' => Department::count(),
                'totalAssignedUsers' => User::count(),
            ],
            'hoRefs' => $this->service->getHeadOfficeRefs(),
            'storeAreas' => $this->service->getStoreAreas(),
            'storeAreaOptions' => $this->service->getStoreAreaOptions(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Departments/Create', [
            'hoRefs' => $this->service->getHeadOfficeRefs(),
            'storeAreas' => $this->service->getStoreAreas(),
            'storeAreaOptions' => $this->service->getStoreAreaOptions(),
        ]);
    }

    public function store(StoreDepartmentRequest $request)
    {
        Gate::authorize('create', Department::class);

        $this->service->create($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        return Inertia::render('Admin/Departments/Edit', [
            'department' => $department->load('externalReference'),
            'hoRefs' => $this->service->getHeadOfficeRefs(),
            'storeAreas' => $this->service->getStoreAreas(),
            'storeAreaOptions' => $this->service->getStoreAreaOptions(),
        ]);
    }

    public function update(StoreDepartmentRequest $request, Department $department)
    {
        Gate::authorize('update', $department);

        $this->service->update($department, $request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        Gate::authorize('delete', $department);

        $this->service->delete($department);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    public function storeRefsByArea(string $area)
    {
        return response()->json($this->service->getStoreRefsByArea($area));
    }
}
