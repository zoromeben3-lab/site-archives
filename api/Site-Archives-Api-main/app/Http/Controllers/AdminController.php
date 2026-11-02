<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Filiere;
use App\Models\Historique;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
     * Liste des comptes utilisateurs pour l'administration (CDC p.10).
     * Endpoint : GET /api/admin/users
     */
    public function users(): JsonResponse
    {
        $users = User::with('filiere:id,nom,code')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ], 200);
    }

    /**
     * Création d'un nouvel utilisateur (chef de filière, enseignant, admin).
     * Endpoint : POST /api/admin/users
     */
    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:etudiant,chef_filiere,admin',
            'filiere_id' => 'nullable|required_if:role,chef_filiere|exists:filieres,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'filiere_id' => $validated['role'] === 'chef_filiere' ? $validated['filiere_id'] : null,
        ]);

        $user->load('filiere');

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur créé avec succès.',
            'data' => $user,
        ], 201);
    }
}
