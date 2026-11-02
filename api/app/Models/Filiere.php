<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    use HasFactory;

    // Colonnes autorisées à la création / modification
    protected $fillable = [
        'nom',
        'code',
        'description',
    ];

    // Une filière possède plusieurs modules de cours.

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    // Une filière peut avoir des chefs de filière ou étudiants qui lui sont rattachés.

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

}
