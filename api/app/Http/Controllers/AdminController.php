<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Filiere;
use App\Models\Historique;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Tableau de bord : Statistiques générales du système (CDC p.10).
     * Endpoint : GET /api/admin/stats
     */
    public function stats(): JsonResponse
    {
        $totalArchives = Archive::count();
        $totalDownloads = Archive::sum('downloads_count');
        $totalFilieres = Filiere::count();
        $totalModules = Module::count();

        $usersCount = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'chefs_filiere' => User::where('role', 'chef_filiere')->count(),
            'etudiants' => User::where('role', 'etudiant')->count(),
        ];

        // Dernières activités enregistrées
        $recentActivities = Historique::with('user:id,name,role')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_archives' => $totalArchives,
                'total_downloads' => (int) $totalDownloads,
                'total_filieres' => $totalFilieres,
                'total_modules' => $totalModules,
                'users' => $usersCount,
                'recent_activities' => $recentActivities,
            ],
        ], 200);
    }

    /**
     * Consultation des journaux d'activité / audit de sécurité (CDC p.10 & 16).
     * Endpoint : GET /api/admin/historiques
     */
    public function historiques(Request $request): JsonResponse
    {
        $query = Historique::with([
            'user:id,name,email,role',
            'archive:id,titre,module_id',
        ]);

        // Filtrer par type d'action (upload, update, delete, download)
        $query->when($request->filled('action'), function ($q) use ($request) {
            $q->where('action', $request->action);
        });

        // Filtrer par utilisateur
        $query->when($request->filled('user_id'), function ($q) use ($request) {
            $q->where('user_id', $request->user_id);
        });

        $logs = $query->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ], 200);
    }

    /**
     * Liste des comptes (format front : prenom, nom, identifiant, role, actif).
     * Endpoint : GET /api/admin/users?q=&role=
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::query();

        $query->when($request->filled('q'), function ($q) use ($request) {
            $q->where(fn ($s) => $s->where('name', 'like', "%{$request->q}%")
                ->orWhere('email', 'like', "%{$request->q}%"));
        });

        $query->when($request->filled('role') && $request->role !== 'Tous', function ($q) use ($request) {
            $q->where('role', $request->role === 'chef' ? 'chef_filiere' : $request->role);
        });

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('name')->get()->map->versFront()->values(),
        ], 200);
    }

    /**
     * Création d'un compte par l'admin. Le mot de passe temporaire est renvoyé UNE fois.
     * Endpoint : POST /api/admin/users
     */
    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prenom' => 'required|string|max:50',
            'nom' => 'required|string|max:50',
            'identifiant' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'role' => 'required|in:etudiant,chef,chef_filiere,admin',
            'filiere_id' => 'nullable|exists:filieres,id',
            'filiere' => 'nullable|string',
        ]);

        $role = $validated['role'] === 'chef' ? 'chef_filiere' : $validated['role'];
        $email = strtolower(trim($validated['identifiant'])) . '@u-auben.bf';

        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'Cet identifiant est déjà utilisé.'], 409);
        }

        $filiereId = $validated['filiere_id'] ?? null;
        if (! $filiereId && ! empty($validated['filiere'])) {
            $filiereId = Filiere::where('nom', $validated['filiere'])->value('id');
        }
        if ($role === 'chef_filiere' && ! $filiereId) {
            return response()->json(['message' => 'Un chef de filière doit être rattaché à une filière.'], 422);
        }

        $motDePasseTemporaire = Str::password(12, symbols: false);

        $user = User::create([
            'name' => trim($validated['prenom']) . ' ' . trim($validated['nom']),
            'email' => $email,
            'password' => $motDePasseTemporaire,
            'role' => $role,
            'filiere_id' => $role === 'chef_filiere' ? $filiereId : null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur créé avec succès.',
            'mot_de_passe_temporaire' => $motDePasseTemporaire,
            'data' => $user->versFront(),
        ], 201);
    }

    /**
     * Changer le rôle, la filière ou l'état d'un compte.
     * Endpoint : PATCH /api/admin/users/{id}
     */
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas modifier votre propre rôle ni désactiver votre propre compte.',
            ], 403);
        }

        $validated = $request->validate([
            'role' => 'sometimes|in:etudiant,chef,chef_filiere,admin',
            'actif' => 'sometimes|boolean',
            'filiere_id' => 'sometimes|nullable|exists:filieres,id',
        ]);

        if (isset($validated['role'])) {
            $validated['role'] = $validated['role'] === 'chef' ? 'chef_filiere' : $validated['role'];
        }

        $roleFinal = $validated['role'] ?? $user->role;
        $filiereFinale = array_key_exists('filiere_id', $validated) ? $validated['filiere_id'] : $user->filiere_id;
        if ($roleFinal === 'chef_filiere' && ! $filiereFinale) {
            return response()->json(['message' => 'Un chef de filière doit être rattaché à une filière.'], 422);
        }
        if ($roleFinal !== 'chef_filiere') {
            $validated['filiere_id'] = null;
        }

        $user->update($validated);

        // Compte désactivé ou rôle changé : on coupe les sessions ouvertes
        if (isset($validated['actif']) && ! $validated['actif'] || isset($validated['role'])) {
            $user->tokens()->delete();
        }

        return response()->json(['status' => 'success', 'data' => $user->fresh()->versFront()], 200);
    }

    /**
     * Suppression d'un compte.
     * Endpoint : DELETE /api/admin/users/{id}
     */
    public function deleteUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 403);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['status' => 'success', 'message' => 'Compte supprimé.'], 200);
    }
}
