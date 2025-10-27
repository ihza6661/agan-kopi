<?php

namespace App\Http\Middleware;

use App\Enums\RoleStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Ensure authenticated user has any of the required roles.
     * Usage in alias: EnsureUserRole:admin or EnsureUserRole:admin,cashier
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            abort(403);
        }
        $userRole = (string) Auth::user()->role;
        $valid = array_map(fn(RoleStatus $r) => $r->value, RoleStatus::cases());
        // If alias provides roles, restrict to those; otherwise allow any known role
        $allowed = !empty($roles) ? $roles : $valid;
        if (!in_array($userRole, $allowed, true)) {
            abort(403);
        }
        return $next($request);
    }
}
