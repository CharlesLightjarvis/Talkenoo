<?php

namespace App\Http\Services\V1;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAllUsers() 
    {
        return User::paginate(10);
    }   

    public function createUser(array $userData)
    {
        if(isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }
        return User::create($userData);
    }

    public function updateUser(User $user, array $userData)
    {
        if(isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }
        return $user->update($userData);
    }

    public function deleteUser(User $user)
    {
        return $user->delete();
    }

    public function getUserById(User $user)
    {
        return $user;
    }
}