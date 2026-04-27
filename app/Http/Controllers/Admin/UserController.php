<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use App\Models\Department;
use App\Services\UserService;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function index()
    {
        // Eager load the department to avoid N+1 query problems!
        $users = User::with('department')->latest()->get();
        $departments = Department::all(); // Needed for the create user dropdown

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'departments' => $departments
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->createUser($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }
}