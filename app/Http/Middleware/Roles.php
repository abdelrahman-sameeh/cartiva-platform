<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;



class Roles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$selected_roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $selectedRoles = array_map('strtolower', $selected_roles);
        $allowedRoles = array_column(RoleName::cases(), 'value');

        foreach ($selectedRoles as $role) {
            abort_unless(in_array($role, $allowedRoles, true), 500, "Invalid role {$role}");
        }

        abort_unless($user->hasAnyRole($selectedRoles), 403, 'Forbidden');

        return $next($request);
    }
}
