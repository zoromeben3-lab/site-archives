<?php

namespace App\Http\Controllers;

use App\Models\Actualite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActualiteController extends Controller
{
    // GET /api/actualites
    public function index(): JsonResponse
    {
        $data = Actualite::with('user:id,name')->latest()->take(50)->get()->map(fn (Actualite $a) => [
            'id' => $a->id,
            'titre' => $a->titre,
            'texte' => $a->texte,
            'date' => $a->created_at->format('Y-m-d'),
            'auteur' => $a->user?->name,
        ]);

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    // POST /api/admin/actualites
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:150',
            'texte' => 'required|string|max:2000',
        ]);

        $actu = Actualite::create($validated + ['user_id' => $request->user()->id]);

        return response()->json(['status' => 'success', 'data' => $actu], 201);
    }

    // DELETE /api/admin/actualites/{id}
    public function destroy(int $id): JsonResponse
    {
        Actualite::findOrFail($id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Actualité supprimée.'], 200);
    }
}
