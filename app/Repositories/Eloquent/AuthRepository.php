<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    public function createUser(array $data)
    {
        // Implement the logic to create a new user using the provided data
        return User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'] ?? 'passenger', // Default role is 'passenger'
        ]);
    }

    public function findByEmail(string $email)
    {
        // Implement the logic to find a user by their email address
        return User::where('email', $email)->first();
    }
}