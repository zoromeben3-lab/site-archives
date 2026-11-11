<?php

use App\Http\Controllers\ActualiteController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\MoiController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\ReferentielController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques (pas de jeton)
|--------------------------------------------------------------------------
*/
// Authentification : tentatives limitées (anti force brute)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

// Référentiels (listes déroulantes, non sensibles)
Route::get('/filieres', [ReferentielController::class, 'filieres']);
Route::get('/niveaux', [ReferentielController::class, 'niveaux']);
Route::get('/annees-academiques', [ReferentielController::class, 'anneesAcademiques']);



/*
|--------------------------------------------------------------------------
| Routes protégées (Bearer token Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/password', [AuthController::class, 'changerMotDePasse']);

    // Consultation (réservée aux utilisateurs connectés)
    Route::get('/modules', [ModuleController::class, 'index']);
    Route::get('/actualites', [ActualiteController::class, 'index']);
    Route::get('/programmes', [ProgrammeController::class, 'index']);
    Route::get('/archives', [ArchiveController::class, 'index']);
    Route::get('/archives/{id}', [ArchiveController::class, 'show']);

    // Téléchargement : réservé aux utilisateurs connectés
    Route::get('/archives/{id}/download', [ArchiveController::class, 'download']);

    // Historique personnel de téléchargements
    Route::get('/moi/telechargements', [MoiController::class, 'telechargements']);
    Route::delete('/moi/telechargements', [MoiController::class, 'viderTelechargements']);

    // Chef de filière + Admin
    Route::middleware('role:admin,chef_filiere')->group(function () {
        Route::post('/archives', [ArchiveController::class, 'store']);
        Route::put('/archives/{id}', [ArchiveController::class, 'update']);
        Route::patch('/archives/{id}', [ArchiveController::class, 'update']);
        Route::delete('/archives/{id}', [ArchiveController::class, 'destroy']);

        Route::post('/programmes', [ProgrammeController::class, 'store']);
        Route::put('/programmes', [ProgrammeController::class, 'store']);
    });

    // Admin uniquement
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::post('/modules', [ModuleController::class, 'store']);
        Route::delete('/modules/{id}', [ModuleController::class, 'destroy']);

        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/historiques', [AdminController::class, 'historiques']);

        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::patch('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        Route::post('/actualites', [ActualiteController::class, 'store']);
        Route::delete('/actualites/{id}', [ActualiteController::class, 'destroy']);
    });
});
