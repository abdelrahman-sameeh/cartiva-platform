<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return $user->load('roles');
    }

    public function authenticate(array $credentials, bool $remember = false): ?User
    {
        if (! Auth::guard('web')->attempt($credentials, $remember)) {
            return null;
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        return $user->loadMissing('roles');
    }

    public function login(User $user, bool $remember = false): void
    {
        Auth::guard('web')->login($user, $remember);
    }

    public function createApiToken(User $user, string $name = 'auth_token'): string
    {
        return $user->createToken($name)->plainTextToken;
    }

    public function logoutFromApi(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function logoutFromWeb(): void
    {
        Auth::guard('web')->logout();
    }

}
