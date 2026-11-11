<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use App\Models\Programme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProgrammeController extends Controller
{
    // GET /api/programmes?filiere=&semestre=
    public function index(Request $request): JsonResponse
    {
        $query = Programme::with('filiere:id,nom');

        $query->when($request->filled('filiere'), fn ($q) => $q->whereHas(
            'filiere', fn ($f) => $f->where('nom', $request->filiere)));
        $query->when($request->filled('semestre'), fn ($q) => $q->where(
            'semestre', (int) preg_replace('/\D/', '', $request->semestre)));

        $data = $query->get()->map(fn (Programme $p) => [
            'filiere' => $p->filiere->nom,
            'semestre' => $p->semestre,
            'url' => Storage::disk('public')->url($p->file_path),
        ]);

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    // PUT /api/programmes  (multipart : filiere, semestre, image) — chef (sa filière) et admin
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'filiere' => 'required|string',
            'semestre' => 'required',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // 5 Mo
        ], [
            'image.max' => 'La taille maximale autorisée pour un programme est de 5 Mo.',
        ]);

        $semestre = (int) preg_replace('/\D/', '', (string) $request->semestre);
        if ($semestre < 1 || $semestre > 6) {
            return response()->json(['message' => 'Semestre invalide (1 à 6).'], 422);
        }

        $filiere = Filiere::where('nom', $request->filiere)->first();
        if (! $filiere) {
            return response()->json(['message' => 'Filière introuvable.'], 422);
        }

        $user = $request->user();
        if ($user->isChefFiliere() && $user->filiere_id !== $filiere->id) {
            return response()->json(['message' => 'Vous ne pouvez modifier que le programme de votre filière.'], 403);
        }

        $existant = Programme::where(['filiere_id' => $filiere->id, 'semestre' => $semestre])->first();
        if ($existant) {
            Storage::disk('public')->delete($existant->file_path);
        }

        $chemin = $request->file('image')->store('programmes', 'public');

        $programme = Programme::updateOrCreate(
            ['filiere_id' => $filiere->id, 'semestre' => $semestre],
            ['file_path' => $chemin]
        );

        return response()->json([
            'status' => 'success',
            'data' => ['filiere' => $filiere->nom, 'semestre' => $semestre,
                'url' => Storage::disk('public')->url($programme->file_path)],
        ], 200);
    }
}
