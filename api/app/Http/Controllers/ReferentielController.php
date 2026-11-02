<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\Filiere;
use App\Models\Niveau;
use Illuminate\Http\JsonResponse;

class ReferentielController extends Controller
{
    /**
     * Liste toutes les filières disponibles.
     * Endpoint : GET /api/filieres
     */
    public function filieres(): JsonResponse
    {
        $filieres = Filiere::orderBy('nom', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $filieres,
        ], 200);
    }

    /**
     * Liste tous les niveaux d'études (L1, L2, L3, M1, M2).
     * Endpoint : GET /api/niveaux
     */
    public function niveaux(): JsonResponse
    {
        $niveaux = Niveau::orderBy('id', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $niveaux,
        ], 200);
    }

    /**
     * Liste toutes les années académiques (ex: 2024-2025).
     * Endpoint : GET /api/annees-academiques
     */
    public function anneesAcademiques(): JsonResponse
    {
        $annees = AnneeAcademique::orderBy('annee', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $annees,
        ], 200);
    }
}
