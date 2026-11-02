<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Niveau extends Model
{
    use HasFactory;

    // Précise à Laravel le nom exact de la table en français
    protected $table = 'niveaux';

     protected $fillable = [
        'code',
        'intitule',
    ];

    // Un niveau peut avoir plusieurs modules

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }
    
}
