<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Filiere;
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

        if (! $user->actif) {
            return response()->json([
                'message' => 'Ce compte est désactivé. Contactez un administrateur.',
            ], 403);
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

    /**
     * Inscription ouverte : crée TOUJOURS un compte étudiant (le rôle n'est jamais choisi par le client).
     * Endpoint : POST /api/register
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'prenom' => 'required|string|max:50',
            'nom' => 'required|string|max:50',
            'identifiant' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'mdp' => 'required|string|min:8|max:100',
            'filiere' => 'nullable|string|max:100',
        ], [
            'identifiant.regex' => 'Identifiant : lettres, chiffres, point, tiret ou souligné uniquement.',
            'mdp.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        $email = strtolower(trim($request->identifiant)) . '@u-auben.bf';

        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'Cet identifiant est déjà utilisé.'], 409);
        }

        $user = User::create([
            'name' => trim($request->prenom) . ' ' . trim($request->nom),
            'email' => $email,
            'password' => Hash::make($request->mdp),
            'role' => 'etudiant',
            'filiere_id' => $request->filled('filiere')
                ? Filiere::where('nom', $request->filiere)->value('id')
                : null,
        ]);

        // Connexion automatique : même format de réponse que /login
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Compte créé avec succès.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->versFront() + ['name' => $user->name],
        ], 201);
    }

    /**
     * Changement du mot de passe de l'utilisateur connecté.
     * Endpoint : PUT /api/password
     */
    public function changerMotDePasse(Request $request): JsonResponse
    {
        $request->validate([
            'ancien' => 'required|string',
            'nouveau' => 'required|string|min:8|max:100|different:ancien',
        ], [
            'nouveau.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'nouveau.different' => "Le nouveau mot de passe doit être différent de l'ancien.",
        ]);

        $user = $request->user();

        if (! Hash::check($request->ancien, $user->password)) {
            return response()->json(['message' => 'Ancien mot de passe incorrect.'], 422);
        }

        $user->update(['password' => $request->nouveau]); // haché par le cast « hashed »

        // Révoque les autres sessions, garde celle en cours
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }
}
