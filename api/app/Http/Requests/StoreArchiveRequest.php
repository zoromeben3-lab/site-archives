<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArchiveRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        // L'utilisateur doit être connecté et être admin ou chef de filière
        $user = $this->user();
        return $user && ($user->isAdmin() || $user->isChefFiliere());
    }

    /**
     * Préparation des données pour la validation (résolution souple des clés envoyées par Angular).
     */
    protected function prepareForValidation(): void
    {
        // 1. Résolution du type s'il est en français frontend (ex: 'Épreuve de session')
        if ($this->has('type')) {
            $type = strtolower(trim($this->input('type')));
            if (str_contains($type, 'session') || str_contains($type, 'preuve')) {
                $this->merge(['type' => 'examen']);
            } else {
                $this->merge(['type' => $type]);
            }
        }

        // 2. Résolution du module_id depuis le nom du module si nécessaire
        if (!$this->has('module_id') || empty($this->input('module_id'))) {
            $nomModule = $this->input('module');
            if ($nomModule) {
                $module = \App\Models\Module::where('nom', trim($nomModule))->first();
                if ($module) {
                    $this->merge(['module_id' => $module->id]);
                }
            }
        }

        // 3. Résolution de l'année académique depuis l'année texte si nécessaire
        if (!$this->has('annee_academique_id') || empty($this->input('annee_academique_id'))) {
            $texteAnnee = $this->input('annee');
            if ($texteAnnee) {
                $anneeObj = \App\Models\AnneeAcademique::where('annee', trim($texteAnnee))
                    ->orWhere('annee', str_replace(' ', '', trim($texteAnnee)))
                    ->first();
                if ($anneeObj) {
                    $this->merge(['annee_academique_id' => $anneeObj->id]);
                }
            }

            // Si toujours pas trouvé, on prend l'année active par défaut
            if (!$this->has('annee_academique_id') || empty($this->input('annee_academique_id'))) {
                $anneeActive = \App\Models\AnneeAcademique::where('active', true)->first()
                    ?? \App\Models\AnneeAcademique::first();
                if ($anneeActive) {
                    $this->merge(['annee_academique_id' => $anneeActive->id]);
                }
            }
        }
    }

    /**
     * Règles de validation pour l'upload d'une archive.
     */
    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:devoir,test,examen,autre',
            'module_id' => 'required|exists:modules,id',
            'annee_academique_id' => 'required|exists:annee_academiques,id',
            'file' => 'required|file|mimes:pdf,doc,docx,odt,jpg,jpeg,png|max:10240', // Max 10 Mo
        ];
    }

    /**
     * Messages personnalisés de validation.
     */
    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre du document est obligatoire.',
            'type.required' => 'Le type de document (devoir, test, examen ou autre) est obligatoire.',
            'type.in' => 'Le type doit être : devoir, test, examen ou autre.',
            'module_id.required' => 'Le module associé est obligatoire.',
            'module_id.exists' => 'Le module sélectionné est introuvable.',
            'annee_academique_id.required' => 'L\'année académique est obligatoire.',
            'annee_academique_id.exists' => 'L\'année académique sélectionnée est introuvable.',
            'file.required' => 'Veuillez joindre le fichier de l\'archive.',
            'file.mimes' => 'Le format du fichier doit être un PDF, DOC, DOCX, ODT ou une image (JPG, PNG).',
            'file.max' => 'La taille maximale autorisée pour le document est de 10 Mo.',
        ];
    }
}
