<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ReferentielController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Publiques (sans authentification obligatoire)
|--------------------------------------------------------------------------
*/
// Authentification
Route::post('/login', [AuthController::class, 'login']);

// Référentiels pour les listes déroulantes de recherche
Route::get('/filieres', [ReferentielController::class, 'filieres']);
Route::get('/niveaux', [ReferentielController::class, 'niveaux']);
Route::get('/annees-academiques', [ReferentielController::class, 'anneesAcademiques']);
Route::get('/modules', [ModuleController::class, 'index']);

// Consultation et téléchargement des archives (étudiants & visiteurs autorisés)
Route::get('/archives', [ArchiveController::class, 'index']);
Route::get('/archives/{id}', [ArchiveController::class, 'show']);
Route::get('/archives/{id}/download', [ArchiveController::class, 'download']);

/*
|--------------------------------------------------------------------------
| Routes Protégées (Nécessitent un Bearer Token Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Profil utilisateur & Déconnexion
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestion des archives (Dépôt, modification, suppression par Chef de filière et Admin)
    Route::middleware('role:admin,chef_filiere')->group(function () {
        Route::post('/archives', [ArchiveController::class, 'store']);
        Route::put('/archives/{id}', [ArchiveController::class, 'update']);
        Route::patch('/archives/{id}', [ArchiveController::class, 'update']);
        Route::delete('/archives/{id}', [ArchiveController::class, 'destroy']);
    });

    // Administration Système (réservé exclusivement à l'Admin)
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::post('/modules', [ModuleController::class, 'store']);
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/historiques', [AdminController::class, 'historiques']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'createUser']);
    });
});
