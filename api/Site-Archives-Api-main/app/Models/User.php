<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'filiere_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // La filière à laquelle appartient l'utilisateur (utile pour le chef de filière).
     
    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    // Les archives déposées par cet utilisateur.
     
    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }

    // Les actions enregistrées dans l'historique par cet utilisateur.
     
    public function historiques(): HasMany
    {
        return $this->hasMany(Historique::class);
    }

    // Méthodes d'aide (helpers) très pratiques pour tester les rôles
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isChefFiliere(): bool
    {
        return $this->role === 'chef_filiere';
    }

    public function isEtudiant(): bool
    {
        return $this->role === 'etudiant';
    }
}
