<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Programme extends Model
{
    protected $fillable = ['filiere_id', 'semestre', 'file_path'];

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }
}
