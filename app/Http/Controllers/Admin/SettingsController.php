<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\PermissionService;
use App\Support\CardSettings;
use App\Shared\Enums\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Contrôleur pour les paramètres de l'application
 */
class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:PARAMETRES_READ,PARAMETRES_MANAGE,UTILISATEUR_READ,PERMISSION_READ')->only(['index']);
        $this->middleware('permission:PARAMETRES_MANAGE')->only(['update']);
        $this->middleware('permission:PERMISSION_MANAGE')->only(['storePermission', 'updatePermission', 'destroyPermission']);
        $this->middleware('permission:PERMISSION_ASSIGN,PERMISSION_MANAGE')->only(['assignPermissions']);
    }

    /**
     * Afficher la page des paramètres
     */
    public function index()
    {
        $u = auth()->user();
        $ctx = $u->scopeContext();

        // Utilisateurs visibles selon le périmètre (fidèle à Parametres.jsx).
        $users = \App\Models\User::with('permissions')
            ->whereIn('role', ['federation', 'ligue', 'maitre'])
            ->when($ctx['scoped'] && $ctx['salle_id'], fn ($q) => $q->where('salle_id', $ctx['salle_id']))
            ->when($ctx['scoped'] && !$ctx['salle_id'] && $ctx['ligue_id'], fn ($q) => $q->where('ligue_id', $ctx['ligue_id']))
            ->when($ctx['scoped'] && !$ctx['ligue_id'] && $ctx['federation_id'], fn ($q) => $q->where('federation_id', $ctx['federation_id']))
            ->orderBy('name')
            ->get();

        $allPermissions = Permission::where('is_active', true)->orderBy('order')->orderBy('module')->get();

        // Assigner Permission : le superadmin doit pouvoir assigner une permission à
        // n'importe quel utilisateur, y compris hors du périmètre fédération/ligue/
        // salle et hors des rôles federation/ligue/maitre (ex. un autre admin). Les
        // autres rôles restent sur la liste déjà scopée ($users) ci-dessus.
        $assignableUsers = $u->isSuperAdmin()
            ? \App\Models\User::with('permissions')->orderBy('name')->get()
            : $users;

        // Rôles que l'utilisateur courant peut créer (hiérarchie fidèle à Parametres.jsx).
        $assignableRoles = \App\Shared\Enums\UserRole::assignableBy($u);
        $roleOptions = collect($assignableRoles)
            ->mapWithKeys(fn (\App\Shared\Enums\UserRole $r) => [$r->value => $r->label()])
            ->toArray();

        // Options de fonction par rôle cible (président fédération/ligue, délégués…), pour le JS du modal.
        $functionOptions = collect($assignableRoles)
            ->mapWithKeys(fn (\App\Shared\Enums\UserRole $r) => [$r->value => \App\Shared\Enums\UserRole::functionOptions($u, $r->value)])
            ->toArray();

        return view('admin.settings', [
            // Onglets du hub Paramètres (comme Parametres.jsx)
            'users' => $users,
            'federations' => \App\Models\Federation::visibleTo($u)->withCount(['ligues', 'grades'])->orderBy('nom')->get(),
            'ligues' => \App\Models\Ligue::visibleTo($u)->with('federation:id,nom')->withCount('salles')->orderBy('nom')->get(),
            'salles' => \App\Models\Salle::visibleTo($u)->with(['ligue:id,nom', 'maitre:id,nom_complet', 'maitreUser:id,salle_id,name,grade_id', 'maitreUser.grade:id,nom_grade'])->withCount('disciples')->orderBy('nom')->get(),
            'grades' => \App\Models\Grade::visibleTo($u)->with('federation:id,nom')->orderBy('type_grade')->orderBy('niveau')->get(),
            'maitres' => \App\Models\Maitre::visibleTo($u)->orderBy('nom_complet')->get(['id', 'nom_complet']),
            'permissions' => $allPermissions,
            'permissionsByModule' => $allPermissions->groupBy('module'),
            // On ne s'assigne pas de permissions à soi-même depuis cet écran.
            'assignableUsers' => $assignableUsers->reject(fn ($usr) => $usr->id === $u->id)->values(),
            'roleOptions' => $roleOptions,
            'functionOptions' => $functionOptions,
            'ctx' => $ctx,
            'page_title' => __('messages.settings'),
            'active_menu' => 'settings',
        ]);
    }

    /**
     * Stocker une nouvelle permission
     */
    public function storePermission(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'module' => 'required|string|max:255',
        ], [
            'name.required' => 'Le nom de la permission est obligatoire.',
            'name.max' => 'Le nom de la permission ne doit pas dépasser 255 caractères.',
            'module.required' => 'Le module est obligatoire.',
            'module.max' => 'Le module ne doit pas dépasser 255 caractères.',
        ]);

        $validated = $this->preparePermissionData($validated);
        $this->authorizeSchoolPermissionData($validated);

        $existingPermission = Permission::withTrashed()
            ->where('name', $validated['name'])
            ->orWhere('slug', $validated['slug'])
            ->first();

        if ($existingPermission) {
            if ($existingPermission->trashed()) {
                $existingPermission->restore();
                $existingPermission->update($validated);

                return redirect()->route('admin.settings', ['tab' => 'permissions-list'])
                    ->with('success', 'Permission restaurée avec succès');
            }

            return redirect()->route('admin.settings', ['tab' => 'permissions-list'])
                ->with('warning', "La permission '{$existingPermission->name}' existe déjà dans le module {$existingPermission->module}.");
        }

        try {
            Permission::create($validated);
        } catch (QueryException $exception) {
            return redirect()->route('admin.settings', ['tab' => 'permissions-list'])
                ->withInput($request->input())
                ->with('warning', 'Cette permission existe déjà ou utilise un identifiant interne déjà présent.');
        }

        return redirect()->route('admin.settings', ['tab' => 'permissions-list'])
            ->with('success', 'Permission ajoutée avec succès');
    }

    /**
     * Mettre à jour une permission
     */
    public function updatePermission(Request $request, Permission $permission): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permission->id),
            ],
            'module' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('permissions', 'slug')->ignore($permission->id),
            ],
        ], [
            'name.required' => 'Le nom de la permission est obligatoire.',
            'name.unique' => 'Une permission avec ce nom existe déjà. Veuillez choisir un autre nom.',
            'name.max' => 'Le nom de la permission ne doit pas dépasser 255 caractères.',
            'module.required' => 'Le module est obligatoire.',
            'module.max' => 'Le module ne doit pas dépasser 255 caractères.',
            'slug.unique' => 'Cet identifiant interne existe déjà.',
            'slug.max' => 'L\'identifiant interne ne doit pas dépasser 255 caractères.',
        ]);

        $validated = $this->preparePermissionData($validated, $permission);
        $this->authorizeSchoolPermission($permission);
        $this->authorizeSchoolPermissionData($validated);

        $permission->update($validated);

        return redirect()->route('admin.settings', ['tab' => 'permissions-list'])
            ->with('success', 'Permission modifiée avec succès');
    }

    /**
     * Supprimer une permission
     */
    public function destroyPermission(Permission $permission): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeSchoolPermission($permission);

        $permission->delete();

        return redirect()->route('admin.settings', ['tab' => 'permissions-list'])
            ->with('success', 'Permission supprimée avec succès');
    }

    /** La gestion des permissions elles-mêmes (créer/renommer/supprimer) est réservée au superadmin. */
    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Accès réservé au super-administrateur.');
    }

    private function preparePermissionData(array $data, ?Permission $permission = null): array
    {
        $data['slug'] = $permission?->slug ?? Str::slug($data['name'], '_');
        $data['action'] = Str::before($data['slug'], '_') ?: ($permission?->action ?? 'manage');
        $data['is_active'] = true;

        if (!$permission) {
            $data['order'] = ((int) Permission::where('module', $data['module'])->max('order')) + 1;
        }

        return $data;
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

    private function authorizeSchoolPermissionData(array $data): void
    {
        $module = Str::lower((string) ($data['module'] ?? ''));
        $slug = (string) ($data['slug'] ?? '');

        if (
            !auth()->user()?->isSuperAdmin()
            && (in_array($slug, PermissionService::SCHOOL_PERMISSION_SLUGS, true) || str_contains($module, 'scolaire'))
        ) {
            abort(403, 'Accès refusé.');
        }
    }

    /**
     * Assigner des permissions à un utilisateur
     */
    public function assignPermissions(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if (auth()->id() === $user->id) {
            abort(403, 'Vous ne pouvez pas vous assigner des permissions à vous-même.');
        }

        $currentRole = UserRole::tryFrom(auth()->user()?->role ?? '');
        $targetRole = UserRole::tryFrom($user->role);

        if (!$currentRole || !$targetRole || (!$currentRole->canManage($targetRole) && $currentRole !== UserRole::SUPERADMIN && $currentRole !== UserRole::PRESIDENT)) {
            abort(403, 'Accès refusé.');
        }

        $permissions = $validated['permissions'] ?? [];

        if (!auth()->user()?->isSuperAdmin() && !empty($permissions)) {
            $containsSchoolPermission = Permission::whereIn('id', $permissions)
                ->whereIn('slug', PermissionService::SCHOOL_PERMISSION_SLUGS)
                ->exists();

            if ($containsSchoolPermission) {
                abort(403, 'Accès refusé.');
            }
        }

        // Sync des permissions utilisateur
        $user->permissions()->sync($permissions);

        return redirect()->route('admin.settings', ['tab' => 'permissions-assign'])
            ->with('success', __('messages.settings_page.permissions_updated'));
    }

    /**
     * Mettre à jour les paramètres généraux
     */
    public function update(Request $request)
    {
        $section = $request->input('settings_section');
        $settings = [];

        if ($section === 'official-info') {
            $validated = $request->validate([
                'ministry' => ['required', 'string', 'max:255'],
                'federation' => ['required', 'string', 'max:255'],
                'league' => ['required', 'string', 'max:255'],
                'motto' => ['nullable', 'string', 'max:255'],
                'president_name' => ['nullable', 'string', 'max:255'],
            ]);

            $settings['official'] = [
                'ministry' => $validated['ministry'],
                'federation' => $validated['federation'],
                'league' => $validated['league'],
                'motto' => $validated['motto'] ?? '',
                'president_name' => $validated['president_name'] ?? '',
            ];
        } elseif ($section === 'signature') {
            $request->validate([
                'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'signature_data' => ['nullable', 'string'],
                'stamp' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'remove_stamp' => ['nullable', 'boolean'],
            ]);

            $currentSettings = CardSettings::all();

            if ($request->hasFile('signature')) {
                $settings['signature_path'] = CardSettings::storeFile($request->file('signature'), 'card-settings/signatures');
            }

            if ($request->filled('signature_data')) {
                $settings['signature_path'] = $this->storeSignatureData($request->string('signature_data')->toString());
            }

            if ($request->boolean('remove_stamp')) {
                $this->deleteCardSettingFile($currentSettings['stamp_path'] ?? null);
                $settings['stamp_path'] = null;
            }

            if ($request->hasFile('stamp')) {
                $this->deleteCardSettingFile($currentSettings['stamp_path'] ?? null);
                $settings['stamp_path'] = CardSettings::storeFile($request->file('stamp'), 'card-settings/stamps');
            }
        } elseif ($section === 'card-models') {
            $validated = $request->validate([
                'default_template' => ['required', Rule::in(['classic', 'modern', 'minimal'])],
            ]);

            $settings['card']['default_template'] = $validated['default_template'];
        } elseif ($section === 'appearance') {
            $validated = $request->validate([
                'primary_color' => ['required', 'string', 'max:20'],
                'secondary_color' => ['required', 'string', 'max:20'],
                'background_color' => ['required', 'string', 'max:20'],
                'background_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'decorative_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'remove_background_image' => ['nullable', 'boolean'],
                'remove_decorative_image' => ['nullable', 'boolean'],
            ]);

            $currentSettings = CardSettings::all();
            $settings['card'] = [
                'primary_color' => $validated['primary_color'],
                'secondary_color' => $validated['secondary_color'],
                'background_color' => $validated['background_color'],
            ];

            if ($request->boolean('remove_background_image')) {
                $this->deleteCardSettingFile($currentSettings['card']['background_image_path'] ?? null);
                $settings['card']['background_image_path'] = null;
            }

            if ($request->hasFile('background_image')) {
                $this->deleteCardSettingFile($currentSettings['card']['background_image_path'] ?? null);
                $settings['card']['background_image_path'] = CardSettings::storeFile($request->file('background_image'), 'card-settings/backgrounds');
            }

            if ($request->boolean('remove_decorative_image')) {
                $this->deleteCardSettingFile($currentSettings['card']['decorative_image_path'] ?? null);
                $settings['card']['decorative_image_path'] = null;
            }

            if ($request->hasFile('decorative_image')) {
                $this->deleteCardSettingFile($currentSettings['card']['decorative_image_path'] ?? null);
                $settings['card']['decorative_image_path'] = CardSettings::storeFile($request->file('decorative_image'), 'card-settings/decorations');
            }
        } else {
            abort(422, 'Module de paramètres invalide.');
        }

        CardSettings::save($settings);

        return redirect()->route('admin.settings', ['tab' => $section])
            ->with('success', __('messages.settings_page.updated'));
    }

    private function storeSignatureData(string $dataUri): string
    {
        if (!preg_match('/^data:image\/png;base64,/', $dataUri)) {
            abort(422, 'Signature invalide.');
        }

        $content = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);

        if ($content === false) {
            abort(422, 'Signature invalide.');
        }

        $path = 'card-settings/signatures/signature-' . Str::uuid() . '.png';
        Storage::disk('public')->put($path, $content);

        return $path;
    }

    private function deleteCardSettingFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
