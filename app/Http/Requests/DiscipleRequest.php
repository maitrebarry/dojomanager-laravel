<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscipleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:120'],
            'prenom' => ['required', 'string', 'max:120'],
            'sexe' => ['nullable', 'in:M,F'],
            'date_naissance' => ['nullable', 'date'],
            'date_lieu_naissance' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'date_inscription' => ['required', 'date'],
            'salle_id' => ['required', 'exists:salles,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'date_obtention_grade' => ['nullable', 'date'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'nmle' => ['nullable', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => __('messages.disciples.validation.nom_required'),
            'prenom.required' => __('messages.disciples.validation.prenom_required'),
            'date_inscription.required' => __('messages.disciples.validation.date_inscription_required'),
            'salle_id.required' => __('messages.disciples.validation.salle_required'),
            'salle_id.exists' => __('messages.disciples.validation.salle_exists'),
            'grade_id.exists' => __('messages.disciples.validation.grade_exists'),
            'photo.image' => __('messages.disciples.validation.photo_image'),
            'photo.max' => __('messages.disciples.validation.photo_max'),
        ];
    }
}
