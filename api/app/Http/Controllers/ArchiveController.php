<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArchiveRequest;
use App\Http\Requests\UpdateArchiveRequest;
use App\Models\AnneeAcademique;
use App\Models\Archive;
use App\Models\Historique;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveController extends Controller
{
    /**
     * Liste et recherche paginée des archives.
     * Compatible avec le composant RechercheArchives d'Angular.
     * Endpoint : GET /api/archives
     */
    public function index(Request $request): JsonResponse
    {
        $query = Archive::with([
            'module.filiere:id,nom,code',
            'module.niveau:id,code,intitule',
            'anneeAcademique:id,annee,active',
            'user:id,name,role',
        ]);

        // Filtre par filière (par ID ou par nom exact, en ignorant 'Toutes')
        $query->when($request->filled('filiere_id'), function ($q) use ($request) {
            $q->whereHas('module', fn($m) => $m->where('filiere_id', $request->filiere_id));
        });
        $query->when($request->filled('filiere') && $request->filiere !== 'Toutes', function ($q) use ($request) {
            $q->whereHas('module.filiere', fn($f) => $f->where('nom', $request->filiere));
        });

        // Filtre par niveau (par ID ou par nom exact, en ignorant 'Tous')
        $query->when($request->filled('niveau_id'), function ($q) use ($request) {
            $q->whereHas('module', fn($m) => $m->where('niveau_id', $request->niveau_id));
        });
        $query->when($request->filled('niveau') && $request->niveau !== 'Tous', function ($q) use ($request) {
            $q->whereHas('module.niveau', fn($n) => $n->where('intitule', $request->niveau)->orWhere('code', $request->niveau));
        });

        // Filtre par module (par ID ou par nom, en ignorant 'Tous')
        $query->when($request->filled('module_id'), function ($q) use ($request) {
            $q->where('module_id', $request->module_id);
        });
        $query->when($request->filled('module') && $request->module !== 'Tous', function ($q) use ($request) {
            $q->whereHas('module', fn($m) => $m->where('nom', $request->module));
        });

        // Filtre par année académique (par ID ou par texte, en ignorant 'Toutes')
        $query->when($request->filled('annee_academique_id'), function ($q) use ($request) {
            $q->where('annee_academique_id', $request->annee_academique_id);
        });
        $query->when($request->filled('annee') && $request->annee !== 'Toutes', function ($q) use ($request) {
            $q->whereHas('anneeAcademique', fn($a) => $a->where('annee', $request->annee));
        });

        // Filtre par type d'épreuve (en ignorant 'Tous')
        $query->when($request->filled('type') && $request->type !== 'Tous', function ($q) use ($request) {
            $q->where('type', $request->type);
        });

        // Recherche textuelle sur le titre ou la description
        $search = $request->input('search') ?? $request->input('recherche');
        $query->when(! empty($search), function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('module', fn($m) => $m->where('nom', 'like', "%{$search}%"));
            });
        });

        $paginator = $query->latest()->paginate(15);

        // Transformation personnalisée pour injecter directement les propriétés plates attendues par Angular
        $transformedItems = $paginator->getCollection()->map(function (Archive $a) {
            return $this->formatArchiveForAngular($a);
        });

        $paginator->setCollection($transformedItems);

        return response()->json([
            'status' => 'success',
            'data' => $paginator,
        ], 200);
    }

    /**
     * Détail d'une archive par son ID.
     * Endpoint : GET /api/archives/{id}
     */
    public function show(int $id): JsonResponse
    {
        $archive = Archive::with([
            'module.filiere',
            'module.niveau',
            'anneeAcademique',
            'user:id,name,email,role',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatArchiveForAngular($archive),
        ], 200);
    }

    /**
     * Dépôt et upload sécurisé d'une nouvelle archive.
     * Endpoint : POST /api/archives
     */
    public function store(StoreArchiveRequest $request): JsonResponse
    {
        $user = $request->user();

        // Récupération souple du module (soit par module_id, soit par nom 'module')
        $module = null;
        if ($request->filled('module_id')) {
            $module = Module::find($request->module_id);
        } elseif ($request->filled('module')) {
            $module = Module::where('nom', $request->module)->first();
        }

        if (! $module) {
            return response()->json(['message' => 'Le module spécifié est introuvable.'], 422);
        }

        // Récupération souple de l'année
        $anneeId = $request->input('annee_academique_id');
        if (! $anneeId && $request->filled('annee')) {
            $anneeObj = AnneeAcademique::where('annee', $request->annee)->first();
            $anneeId = $anneeObj?->id;
        }
        if (! $anneeId) {
            $anneeActive = AnneeAcademique::where('active', true)->first();
            $anneeId = $anneeActive?->id ?? 1;
        }

        // Règle du CDC : un chef de filière ne peut déposer que pour SA filière
        if ($user->isChefFiliere() && $user->filiere_id !== $module->filiere_id) {
            return response()->json([
                'message' => 'Action non autorisée : vous ne pouvez déposer des archives que pour votre propre filière.',
            ], 403);
        }

        // Sauvegarde physique du fichier
        $uploadedFile = $request->file('file') ?? $request->file('fichier');
        $filePath = $uploadedFile->store('archives', 'local');

        // Création de l'enregistrement en base
        $archive = Archive::create([
            'titre' => $request->titre,
            'description' => $request->description,
            'type' => $request->type,
            'module_id' => $module->id,
            'annee_academique_id' => $anneeId,
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name_original' => $uploadedFile->getClientOriginalName(),
            'file_size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getClientMimeType() ?: 'application/octet-stream',
            'downloads_count' => 0,
        ]);

        // Historique de traçabilité
        Historique::create([
            'user_id' => $user->id,
            'action' => 'upload',
            'archive_id' => $archive->id,
            'description' => "Dépôt de l'archive '{$archive->titre}' pour le module '{$module->nom}'.",
        ]);

        $archive->load(['module.filiere', 'module.niveau', 'anneeAcademique', 'user:id,name']);

        return response()->json([
            'status' => 'success',
            'message' => 'Archive déposée avec succès.',
            'data' => $this->formatArchiveForAngular($archive),
        ], 201);
    }

    /**
     * Modification d'une archive.
     * Endpoint : PUT/PATCH /api/archives/{id}
     */
    public function update(UpdateArchiveRequest $request, int $id): JsonResponse
    {
        $archive = Archive::with('module')->findOrFail($id);
        $user = $request->user();

        if ($user->isChefFiliere() && $user->filiere_id !== $archive->module->filiere_id) {
            return response()->json([
                'message' => 'Action non autorisée : vous ne pouvez modifier que les archives de votre filière.',
            ], 403);
        }

        $archive->update($request->validated());

        Historique::create([
            'user_id' => $user->id,
            'action' => 'update',
            'archive_id' => $archive->id,
            'description' => "Modification de l'archive '{$archive->titre}'.",
        ]);

        $archive->load(['module.filiere', 'module.niveau', 'anneeAcademique']);

        return response()->json([
            'status' => 'success',
            'message' => 'Archive mise à jour avec succès.',
            'data' => $this->formatArchiveForAngular($archive),
        ], 200);
    }

    /**
     * Suppression d'une archive (Soft Delete).
     * Endpoint : DELETE /api/archives/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $archive = Archive::with('module')->findOrFail($id);
        $user = $request->user();

        if ($user->isChefFiliere() && $user->filiere_id !== $archive->module->filiere_id) {
            return response()->json([
                'message' => 'Action non autorisée : vous ne pouvez supprimer que les archives de votre filière.',
            ], 403);
        }

        $archiveTitre = $archive->titre;
        $archive->delete();

        Historique::create([
            'user_id' => $user->id,
            'action' => 'delete',
            'archive_id' => $archive->id,
            'description' => "Suppression de l'archive '{$archiveTitre}'.",
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Archive supprimée avec succès.',
        ], 200);
    }

    /**
     * Téléchargement sécurisé du document.
     * Endpoint : GET /api/archives/{id}/download
     */
    public function download(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $archive = Archive::findOrFail($id);

        if (! Storage::disk('local')->exists($archive->file_path)) {
            return response()->json([
                'message' => 'Le fichier physique associé est introuvable sur le serveur.',
            ], 404);
        }

        $archive->increment('downloads_count');

        $user = $request->user('sanctum');
        if ($user) {
            Historique::create([
                'user_id' => $user->id,
                'action' => 'download',
                'archive_id' => $archive->id,
                'description' => "Téléchargement du document '{$archive->titre}'.",
            ]);
        }

        return Storage::disk('local')->download($archive->file_path, $archive->file_name_original);
    }

    /**
     * Formate une archive pour qu'elle contienne à la fois le modèle complet
     * et les propriétés directes requises par l'interface Angular de votre binôme.
     */
    private function formatArchiveForAngular(Archive $a): array
    {
        return [
            'id' => $a->id,
            'titre' => $a->titre,
            'description' => $a->description,
            'type' => $a->type,
            'filiere' => $a->module?->filiere?->nom ?? '',
            'filiere_id' => $a->module?->filiere_id,
            'module' => $a->module?->nom ?? '',
            'module_id' => $a->module_id,
            'niveau' => $a->module?->niveau?->intitule ?? '',
            'niveau_id' => $a->module?->niveau_id,
            'annee' => $a->anneeAcademique?->annee ?? '',
            'annee_academique_id' => $a->annee_academique_id,
            'date' => $a->created_at ? $a->created_at->format('d M. Y') : '',
            'fichier' => url("/api/archives/{$a->id}/download"),
            'downloads_count' => $a->downloads_count,
            'user' => $a->user ? [
                'id' => $a->user->id,
                'name' => $a->user->name,
                'role' => $a->user->role,
            ] : null,
        ];
    }
}
