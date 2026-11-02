<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Connexion de l'utilisateur (compatible avec le formulaire Angular de votre binôme).
     * Accepte : identifiant (ex: admin, chef.info, etudiant) OU email, et mdp OU password.
     * Endpoint : POST /api/login
     */
    public function login(Request $request): JsonResponse
    {
        // 1. Récupération souple des identifiants (français ou anglais)
        $identifiant = $request->input('identifiant') ?? $request->input('email');
        $motDePasse = $request->input('mdp') ?? $request->input('password');

        if (! $identifiant || ! $motDePasse) {
            return response()->json([
                'message' => 'Veuillez saisir votre identifiant (ou email) et votre mot de passe.',
            ], 422);
        }

        $identifiantNettoye = trim($identifiant);

        // 2. Recherche de l'utilisateur par email complet ou par préfixe (ex: 'admin' trouve 'admin@u-auben.bf')
        $user = User::where('email', $identifiantNettoye)
            ->orWhere('email', $identifiantNettoye . '@u-auben.bf')
            ->first();

        // 3. Vérification du mot de passe
        if (! $user || ! Hash::check($motDePasse, $user->password)) {
            return response()->json([
                'message' => 'Identifiant ou mot de passe incorrect.',
            ], 401);
        }

        // 4. Création du Token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Décomposition prénom / nom pour l'interface Angular
        $partiesNom = explode(' ', $user->name, 2);
        $prenom = $partiesNom[0] ?? '';
        $nom = $partiesNom[1] ?? '';

        // 6. Rôle unifié (dans Angular : 'chef', dans Laravel : 'chef_filiere')
        $roleAngular = ($user->role === 'chef_filiere') ? 'chef' : $user->role;

        return response()->json([
            'message' => 'Connexion réussie',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'prenom' => $prenom,
                'nom' => $nom,
                'email' => $user->email,
                'role' => $roleAngular, // Permet à Angular d'afficher le bon profil
                'role_backend' => $user->role,
                'filiere_id' => $user->filiere_id,
                'filiere' => $user->filiere ? [
                    'id' => $user->filiere->id,
                    'nom' => $user->filiere->nom,
                    'code' => $user->filiere->code,
                ] : null,
            ],
        ], 200);
    }

    /**
     * Profil de l'utilisateur connecté.
     * Endpoint : GET /api/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('filiere');

        $partiesNom = explode(' ', $user->name, 2);
        $prenom = $partiesNom[0] ?? '';
        $nom = $partiesNom[1] ?? '';
        $roleAngular = ($user->role === 'chef_filiere') ? 'chef' : $user->role;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'prenom' => $prenom,
                'nom' => $nom,
                'email' => $user->email,
                'role' => $roleAngular,
                'role_backend' => $user->role,
                'filiere_id' => $user->filiere_id,
                'filiere' => $user->filiere,
            ],
        ], 200);
    }

    /**
     * Déconnexion.
     * Endpoint : POST /api/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie. Le jeton d’accès a été révoqué.',
        ], 200);
    }
}
