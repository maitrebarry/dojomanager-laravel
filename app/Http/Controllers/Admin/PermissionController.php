<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
        $this->middleware('auth');
        $this->middleware('permission:view_permissions')->only(['index']);
        $this->middleware('permission:manage_permissions')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Afficher la liste des permissions avec la grille d'administration
     */
    public function index(): View
    {
        $modules = $this->permissionService->getModulesWithPermissions();

        return view('admin.permissions.index', [
            'modules' => $modules,
            'page_title' => 'Permissions',
            'active_menu' => 'permissions',
        ]);
    }

    /**
     * Afficher le formulaire de création de permission
     */
    public function create(): View
    {
        return view('admin.permissions.create', [
            'page_title' => 'Créer une permission',
            'active_menu' => 'permissions',
        ]);
    }

    /**
     * Enregistrer une nouvelle permission
     */
    public function store(PermissionRequest $request): RedirectResponse
    {
        $this->permissionService->create($request->validated());

        return redirect()->route('admin.permissions.index')
            ->with('success', 'La permission a été créée avec succès.');
    }

    /**
     * Afficher le formulaire d'édition de permission
     */
    public function edit(Permission $permission): View
    {
        $this->authorizeSchoolPermission($permission);

        return view('admin.permissions.edit', [
            'permission' => $permission,
            'page_title' => 'Modifier ' . $permission->name,
            'active_menu' => 'permissions',
        ]);
    }

    /**
     * Mettre à jour une permission
     */
    public function update(PermissionRequest $request, Permission $permission): RedirectResponse
    {
        $this->authorizeSchoolPermission($permission);

        $this->permissionService->update($permission, $request->validated());

        return redirect()->route('admin.permissions.index')
            ->with('success', 'La permission a été modifiée avec succès.');
    }

    /**
     * Supprimer une permission
     */
    public function destroy(Permission $permission): RedirectResponse
    {
        $this->authorizeSchoolPermission($permission);

        $this->permissionService->delete($permission);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'La permission a été supprimée avec succès.');
    }

    /**
     * Initialiser les permissions par défaut
     */
    public function initializeDefaults(): RedirectResponse
    {
        $this->permissionService->createDefaultPermissions();

        return back()->with('success', 'Les permissions par défaut ont été créées.');
    }

    private function authorizeSchoolPermission(Permission $permission): void
    {
        if (
            in_array($permission->slug, PermissionService::SCHOOL_PERMISSION_SLUGS, true)
            && !auth()->user()?->isSuperAdmin()
        ) {
            abort(403, 'Accès refusé.');
        }
    }
}
