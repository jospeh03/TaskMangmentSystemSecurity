<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user.
     * 
     * @param array $data
     * @return User
     */
    public function register(array $data): User
    {
        // Hash the password before storing
        $data['password'] = Hash::make($data['password']);

        // Create and return the user
        return User::create($data);
    }

    /**
     * Login a user and return the JWT token.
     * 
     * @param array $credentials
     * @return string|null
     */
    public function login(array $credentials): ?string
    {
        // Attempt to authenticate and return the JWT token
        if ($token = auth()->attempt($credentials)) {
            return $token;
        }

        return null;
    }
}
