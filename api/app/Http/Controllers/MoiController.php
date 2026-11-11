<?php

namespace App\Http\Controllers;

use App\Models\Historique;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MoiController extends Controller
{
    // GET /api/moi/telechargements
    public function telechargements(Request $request): JsonResponse
    {
        $data = Historique::with('archive:id,titre')
            ->where('user_id', $request->user()->id)
            ->where('action', 'download')
            ->where('masque', false)
            ->latest()
            ->get()
            ->filter(fn ($h) => $h->archive)
            ->unique('archive_id')
            ->map(fn ($h) => [
                'titre' => $h->archive->titre,
                'fichier' => url("/api/archives/{$h->archive_id}/download"),
                'date' => $h->created_at->format('d/m/Y'),
            ])
            ->values();

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    // DELETE /api/moi/telechargements — masque l'historique (l'audit admin est conservé)
    public function viderTelechargements(Request $request): JsonResponse
    {
        Historique::where('user_id', $request->user()->id)
            ->where('action', 'download')
            ->update(['masque' => true]);

        return response()->json(['status' => 'success', 'message' => 'Historique vidé.'], 200);
    }
}
