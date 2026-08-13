<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $permissionId = $this->route('permission')?->id;

        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:permissions,slug,' . $permissionId,
            'description' => 'nullable|string',
            'module' => 'required|string|max:100',
            'action' => 'required|string|max:100',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la permission est obligatoire.',
            'slug.required' => 'Le slug est obligatoire.',
            'slug.unique' => 'Ce slug est déjà utilisé.',
            'module.required' => 'Le module est obligatoire.',
            'action.required' => 'L\'action est obligatoire.',
        ];
    }
}
