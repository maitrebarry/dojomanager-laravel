<?php


namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {

        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Création automatique de la permission si elle n'existe pas
        foreach ($permissions as $slug) {
            $exists = \App\Models\Permission::where('slug', $slug)->exists();
            if (!$exists) {
                // Générer un nom et module en français à partir du slug
                $nom = ucwords(str_replace(['_', '-'], [' ', ' '], $slug));
                $module = 'Autre';
                if (str_contains($slug, 'user')) $module = 'Utilisateurs';
                if (str_contains($slug, 'permission')) $module = 'Permissions';
                if (str_contains($slug, 'licence')) $module = 'Cartes';
                if (str_contains($slug, 'school_card')) $module = 'Carte Scolaire';
                if (str_contains($slug, 'parametre') || str_contains($slug, 'setting')) $module = 'Paramètres';
                \App\Models\Permission::create([
                    'name' => $nom,
                    'slug' => $slug,
                    'module' => $module,
                    'is_active' => true,
                ]);
            }
        }

        $schoolPermissionRequested = collect($permissions)->contains(
            fn (string $permission) => str_contains($permission, 'school_card')
        );

        // Super admin et président ont accès à tout, sauf le scolaire qui reste accordé explicitement.
        if ($user->hasFullAccess() && (!$schoolPermissionRequested || $user->isSuperAdmin())) {
            return $next($request);
        }

        // Vérifier les permissions
        if (!$user->hasAnyPermission($permissions)) {
            abort(403, 'Vous n\'avez pas la permission d\'accéder à cette page.');
        }

        return $next($request);
    }
}
