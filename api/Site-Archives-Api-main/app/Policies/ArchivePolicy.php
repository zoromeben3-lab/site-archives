<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\Module;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArchivePolicy
{
    use HandlesAuthorization;

    /**
     * N'importe qui (même non connecté) peut lister les archives.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * N'importe qui peut voir le détail d'une archive.
     */
    public function view(?User $user, Archive $archive): bool
    {
        return true;
    }

    /**
     * Seuls les administrateurs et les chefs de filière peuvent déposer des archives.
     */
    public function create(User $user, ?int $moduleId = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isChefFiliere()) {
            // Si un ID de module est fourni, vérifier qu'il appartient à sa filière
            if ($moduleId) {
                $module = Module::find($moduleId);
                return $module && $module->filiere_id === $user->filiere_id;
            }
            return true;
        }

        return false;
    }

    /**
     * Seul l'administrateur ou le chef de la filière concernée peut modifier l'archive.
     */
    public function update(User $user, Archive $archive): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isChefFiliere()) {
            return $user->filiere_id === $archive->module->filiere_id;
        }

        return false;
    }

    /**
     * Seul l'administrateur ou le chef de la filière concernée peut supprimer l'archive.
     */
    public function delete(User $user, Archive $archive): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isChefFiliere()) {
            return $user->filiere_id === $archive->module->filiere_id;
        }

        return false;
    }
}
