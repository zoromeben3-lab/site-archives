<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArchiveRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à modifier une archive.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isAdmin() || $user->isChefFiliere());
    }

    /**
     * Règles de validation pour la modification d'une archive.
     */
    public function rules(): array
    {
        return [
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:devoir,test,examen,autre',
            'module_id' => 'sometimes|required|exists:modules,id',
            'annee_academique_id' => 'sometimes|required|exists:annee_academiques,id',
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre du document ne peut pas être vide.',
            'type.in' => 'Le type doit être : devoir, test, examen ou autre.',
            'module_id.exists' => 'Le module sélectionné est introuvable.',
            'annee_academique_id.exists' => 'L\'année académique sélectionnée est introuvable.',
        ];
    }
}
