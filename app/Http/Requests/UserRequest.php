<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Shared\Enums\UserRole;
use App\Shared\Enums\UserStatus;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Statut par défaut + dérivation du périmètre parent selon le rôle. */
    protected function prepareForValidation(): void
    {
        if (!$this->filled('status')) {
            $this->merge(['status' => UserStatus::ACTIVE->value]);
        }

        // Cohérence du périmètre : un maître porte une salle (ligue+fédération dérivées),
        // une ligue porte une ligue (fédération dérivée), une fédération porte une fédération.
        $role = $this->input('role');

        if ($role === 'maitre' && $this->filled('salle_id')) {
            $salle = \App\Models\Salle::with('ligue')->find($this->input('salle_id'));
            $this->merge([
                'ligue_id' => $salle?->ligue_id,
                'federation_id' => $salle?->ligue?->federation_id,
            ]);
        } elseif ($role === 'ligue' && $this->filled('ligue_id')) {
            $ligue = \App\Models\Ligue::find($this->input('ligue_id'));
            $this->merge([
                'federation_id' => $ligue?->federation_id,
                'salle_id' => null,
            ]);
        } elseif ($role === 'federation') {
            $this->merge(['ligue_id' => null, 'salle_id' => null]);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->input('user_id');
        $isUpdate = $this->isMethod('PUT') || $this->input('_method') === 'PUT';

        // Rôles assignables selon le créateur (hiérarchie multi-tenant DojoManager) + rôles du template.
        $assignableRoles = array_values(array_unique(array_merge(
            ['federation', 'ligue', 'maitre'],
            array_map(fn (UserRole $role) => $role->value, UserRole::assignableBy($this->user()))
        )));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'role' => ['required', 'string', Rule::in($assignableRoles)],
            'status' => ['required', 'string', Rule::in(array_map(fn($s) => $s->value, UserStatus::cases()))],
            // Périmètre multi-tenant
            'federation_id' => ['nullable', 'exists:federations,id'],
            'ligue_id' => ['nullable', 'exists:ligues,id'],
            'salle_id' => ['nullable', 'exists:salles,id'],
            // Fonction (président / segal / dtn / trésorier…) et grade DAN
            'fonction' => ['nullable', 'string', 'max:40'],
            'grade_id' => ['nullable', 'exists:grades,id'],
        ];
        
        if (!$isUpdate) {
            // En création : mot de passe obligatoire
            $rules['password'] = ['required', 'string', 'min:4', 'confirmed'];
        } else {
            // En modification : mot de passe optionnel
            $rules['password'] = ['nullable', 'string', 'min:4', 'confirmed'];
        }
        
        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 4 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Vous ne pouvez attribuer que les rôles autorisés par votre niveau.',
            'status.required' => 'Le statut est obligatoire.',
        ];
    }
}
