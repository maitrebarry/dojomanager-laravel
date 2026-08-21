<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\UserService;
use App\Shared\Enums\UserRole;
use App\Shared\Enums\UserStatus;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    protected UserService $userService;
    protected PermissionService $permissionService;

    public function __construct(UserService $userService, PermissionService $permissionService)
    {
        $this->userService = $userService;
        $this->permissionService = $permissionService;
        $this->middleware('auth');
        $this->middleware('permission:UTILISATEUR_READ')->only(['index', 'show']);
        $this->middleware('permission:UTILISATEUR_CREATE')->only(['create', 'store']);
        $this->middleware('permission:UTILISATEUR_UPDATE')->only(['edit', 'update', 'activate', 'deactivate']);
        $this->middleware('permission:UTILISATEUR_DELETE')->only(['destroy']);
    }

    /**
     * Empêcher les non-superadmins d'accéder aux comptes superadmin
     */
    private function authorizeTarget(User $target): void
    {
        $current = auth()->user();
        if (!$current) {
            abort(403, 'Accès refusé.');
        }

        $currentRole = UserRole::tryFrom($current->role);
        $targetRole = UserRole::tryFrom($target->role);

        if (!$currentRole || !$targetRole) {
            abort(403, 'Accès refusé.');
        }

        if ($currentRole !== UserRole::SUPERADMIN && !$currentRole->canManage($targetRole)) {
            abort(403, 'Accès refusé.');
        }
    }

    private function authorizeActivationTarget(User $target): void
    {
        $this->authorizeTarget($target);

        if (auth()->id() === $target->id) {
            abort(403, 'Vous ne pouvez pas activer ou désactiver votre propre compte.');
        }

        if ($target->role === UserRole::SUPERADMIN->value) {
            abort(403, 'Les comptes Super Administrateur ne peuvent pas être activés ou désactivés depuis cette action.');
        }
    }

    /**
     * Afficher la liste des utilisateurs
     */
    public function index(): View
    {
        $search = request('search');
        $currentUser = auth()->user();
        $visibleRoles = collect(UserRole::visibleBy($currentUser))->pluck('value')->all();
        
        // Construire la requête de base en fonction du rôle
        if ($currentUser->hasFullAccess()) {
            // Superadmin et président: voir tout le monde
            $query = User::query();
        } else {
            // Chaque rôle ne voit que les rôles placés sous lui dans la hiérarchie
            $query = User::whereIn('role', $visibleRoles);
        }

        // Appliquer la recherche si elle existe
        if ($search) {
            $query = $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
            $users = $query->paginate(15);
        } else {
            $users = $query->paginate(15);
        }

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'roles' => collect(UserRole::assignableBy($currentUser))->mapWithKeys(fn($role) => [$role->value => $role->label()])->toArray(),
            'statuses' => collect(UserStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray(),
            'page_title' => __('messages.users.title'),
            'active_menu' => 'users',
        ]);
    }

    /**
     * Afficher le formulaire de création d'utilisateur
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => collect(UserRole::assignableBy(auth()->user()))->mapWithKeys(fn($role) => [$role->value => $role->label()])->toArray(),
            'statuses' => collect(UserStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray(),
            'page_title' => __('messages.users.create'),
            'active_menu' => 'users',
        ]);
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store(UserRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $user = $this->userService->create($request->validated());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('messages.users.created'),
                    'user' => $user,
                ], 201);
            }

            return $this->redirectAfterMutation($request)
                ->with('success', __('messages.users.created'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('UserController@store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => __('messages.users.create_error', ['message' => $e->getMessage()])], 500);
            }
            return back()->with('error', __('messages.users.create_error', ['message' => $e->getMessage()]))->withInput();
        }
    }

    /**
     * Afficher les détails d'un utilisateur
     */
    public function show(User $user): View
    {
        $this->authorizeTarget($user);
        return view('admin.users.show', [
            'user' => $user,
            'page_title' => __('messages.users.details_named', ['name' => $user->name]),
            'active_menu' => 'users',
        ]);
    }

    /**
     * Afficher le formulaire d'édition d'utilisateur
     */
    public function edit(User $user): View
    {
        $this->authorizeTarget($user);
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => collect(UserRole::assignableBy(auth()->user()))->mapWithKeys(fn($role) => [$role->value => $role->label()])->toArray(),
            'statuses' => collect(UserStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray(),
            'page_title' => __('messages.users.edit_named', ['name' => $user->name]),
            'active_menu' => 'users',
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(UserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $this->authorizeTarget($user);
        try {
            $updated = $this->userService->update($user, $request->validated());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('messages.users.updated'),
                    'user' => $user->fresh(),
                ], 200);
            }

            return $this->redirectAfterMutation($request)
                ->with('success', __('messages.users.updated'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('UserController@update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => __('messages.users.update_error', ['message' => $e->getMessage()])], 500);
            }
            return back()->with('error', __('messages.users.update_error', ['message' => $e->getMessage()]))->withInput();
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user): RedirectResponse|JsonResponse
    {
        $this->authorizeTarget($user);
        try {
            $this->userService->delete($user);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => true, 'message' => __('messages.users.deleted')], 200);
            }

            return $this->redirectAfterMutation(request())
                ->with('success', __('messages.users.deleted'));
        } catch (\Exception $e) {
            \Log::error('UserController@destroy failed', ['error' => $e->getMessage()]);
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => __('messages.users.delete_error', ['message' => $e->getMessage()])], 500);
            }
            return back()->with('error', __('messages.users.delete_error', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Restaurer un utilisateur supprimé
     */
    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->find($id);
        
        if (!$user) {
            return back()->with('error', 'Utilisateur non trouvé.');
        }

        $this->authorizeTarget($user);

        $this->userService->restore($user);

        return back()->with('success', 'L\'utilisateur a été restauré avec succès.');
    }

    /**
     * Activer un utilisateur
     */
    public function activate(User $user): RedirectResponse
    {
        $this->authorizeActivationTarget($user);
        $this->userService->activate($user);

        return back()->with('success', 'L\'utilisateur a été activé avec succès.');
    }

    /**
     * Désactiver un utilisateur, avec un motif optionnel affiché à sa prochaine
     * tentative de connexion.
     */
    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorizeActivationTarget($user);
        $reason = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ])['reason'] ?? null;

        $this->userService->deactivate($user, $reason);

        return back()->with('success', 'L\'utilisateur a été désactivé avec succès.');
    }

    /**
     * Afficher le formulaire de gestion des permissions
     */
    public function editPermissions(User $user): View
    {
        $this->authorizeTarget($user);
        $grid = $this->permissionService->getAdminGrid($user->id);

        return view('admin.users.permissions', [
            'user' => $user,
            'modules' => $grid['modules'],
            'userPermissions' => $grid['userPermissions'],
            'page_title' => 'Permissions de ' . $user->name,
            'active_menu' => 'users',
        ]);
    }

    private function redirectAfterMutation($request): RedirectResponse
    {
        if ($request->boolean('back_to_settings')) {
            return redirect()->route('admin.settings', ['tab' => 'utilisateurs']);
        }

        return redirect()->route('admin.settings', ['tab' => 'utilisateurs']);
    }

    /**
     * Mettre à jour les permissions d'un utilisateur
     */
    public function updatePermissions(User $user): RedirectResponse
    {
        $this->authorizeTarget($user);
        $permissionIds = request('permissions', []);

        if (!auth()->user()?->isSuperAdmin() && !empty($permissionIds)) {
            $containsSchoolPermission = Permission::whereIn('id', $permissionIds)
                ->whereIn('slug', PermissionService::SCHOOL_PERMISSION_SLUGS)
                ->exists();

            if ($containsSchoolPermission) {
                abort(403, 'Accès refusé.');
            }
        }
        
        $this->userService->syncPermissions($user, $permissionIds);

        return back()->with('success', 'Les permissions de l\'utilisateur ont été mises à jour.');
    }
}
