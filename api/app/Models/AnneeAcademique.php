<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneeAcademique extends Model
{
    use HasFactory;

    protected $table = 'annee_academiques';

    protected $fillable = [
        'annee',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Une année académique contient plusieurs archives.
     */
    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }
}
