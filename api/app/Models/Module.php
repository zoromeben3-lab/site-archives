<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'filiere_id',
        'niveau_id',
        'nom',
        'code',
    ];

    // Le module appartient à une filière.

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    // Le module appartient à un niveau (ex: L1, L2).
    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    // Un module contient plusieurs archives (anciens devoirs, examens).
     
    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }
}
