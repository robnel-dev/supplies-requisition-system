<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Models\Department;
use App\Services\DepartmentService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected DepartmentService $departmentService,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->input('search');

        $users = User::with(['department', 'externalDepartmentReference'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('cost_center', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();

        $departments = Department::with('externalReference')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'departments' => $departments,
            'storeAreas' => $this->departmentService->getStoreAreas(),
            'filters' => [
                'search' => $search
            ],
            'stats' => [
                'total' => $totalUsers,
                'active' => $activeUsers
            ]
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        Gate::authorize('create', User::class);
        $this->userService->createUser($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('update', $user);
        $this->userService->updateUser($user, $request->validated());

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $this->userService->deleteUser($user);

        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function updatePassword(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $request->validate(['password' => 'required|min:8|confirmed']);
        $this->userService->updatePassword($user, $request->password);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }
}
