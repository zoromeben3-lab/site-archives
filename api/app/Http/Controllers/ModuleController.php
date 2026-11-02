<?php

namespace App\Http\Controllers;

use App\Models\Module;
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
     * Création d'un nouveau module (réservé à l'Admin).
     * Endpoint : POST /api/modules
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:modules,code',
            'filiere_id' => 'required|exists:filieres,id',
            'niveau_id' => 'required|exists:niveaux,id',
        ]);

        $module = Module::create($validated);
        $module->load(['filiere', 'niveau']);

        return response()->json([
            'status' => 'success',
            'message' => 'Module créé avec succès.',
            'data' => $module,
        ], 201);
    }
}
