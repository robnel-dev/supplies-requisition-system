<?php

namespace App\Http\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserService
{
    public function createUser(array $data): User
    {
        // Hash the password securely
        $data['password'] = Hash::make($data['password']);
        
        // Attach the ID of the HR Admin who created this user (if logged in)
        $data['created_by'] = Auth::id(); 
        
        return User::create($data);
    }
}