<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use App\Models\Module;
use App\Models\Niveau;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * Liste des modules avec filtres optionnels en cascade.
     * Endpoint : GET /api/modules?filiere_id=1&niveau_id=2
     */
    public function index(Request $request): JsonResponse
    {
        $query = Module::with(['filiere:id,nom,code', 'niveau:id,code,intitule']);

        // Filtre dynamique par filière si le paramètre est présent
        $query->when($request->filled('filiere_id'), function ($q) use ($request) {
            $q->where('filiere_id', $request->filiere_id);
        });

        // Filtre dynamique par niveau si le paramètre est présent
        $query->when($request->filled('niveau_id'), function ($q) use ($request) {
            $q->where('niveau_id', $request->niveau_id);
        });

        $modules = $query->orderBy('nom', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'count' => $modules->count(),
            'data' => $modules,
        ], 200);
    }

    /**
     * Création d'un module (admin). Accepte filiere_id/niveau_id OU les noms (« filiere », « niveau »).
     * Endpoint : POST /api/admin/modules
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->filled('filiere_id') && $request->filled('filiere')) {
            $request->merge(['filiere_id' => Filiere::where('nom', $request->filiere)->value('id')]);
        }
        if (! $request->filled('niveau_id') && $request->filled('niveau')) {
            $request->merge(['niveau_id' => Niveau::where('intitule', $request->niveau)
                ->orWhere('code', $request->niveau)->value('id')]);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:modules,code',
            'filiere_id' => 'required|exists:filieres,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'semestre' => 'nullable|string|max:20',
            'credits' => 'nullable|integer|min:1|max:30',
        ]);

        $existe = Module::where('filiere_id', $validated['filiere_id'])
            ->whereRaw('LOWER(nom) = ?', [mb_strtolower($validated['nom'])])
            ->exists();
        if ($existe) {
            return response()->json(['message' => 'Ce module existe déjà dans cette filière.'], 409);
        }

        $module = Module::create($validated);
        $module->load(['filiere', 'niveau']);

        return response()->json([
            'status' => 'success',
            'message' => 'Module créé avec succès.',
            'data' => $module,
        ], 201);
    }

    /**
     * Suppression d'un module (admin). Refusée s'il contient des archives.
     * Endpoint : DELETE /api/admin/modules/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $module = Module::withCount('archives')->findOrFail($id);

        if ($module->archives_count > 0) {
            return response()->json([
                'message' => "Ce module contient {$module->archives_count} archive(s) : supprimez-les d'abord.",
            ], 409);
        }

        $module->delete();

        return response()->json(['status' => 'success', 'message' => 'Module supprimé.'], 200);
    }
}
