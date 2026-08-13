<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint l'accès à une route à certains rôles (le superadmin passe toujours).
 * Reproduit les PrivateRoute ro!={...} de dojo-frontend/src/App.jsx.
 * Vocabulaire Laravel : superadmin / federation / ligue / maitre.
 */
class RoleGuard
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $role = $user->role instanceof \App\Shared\Enums\UserRole ? $user->role->value : (string) $user->role;

        if (!in_array($role, $roles, true)) {
            abort(403, __('messages.out_of_scope'));
        }

        return $next($request);
    }
}
