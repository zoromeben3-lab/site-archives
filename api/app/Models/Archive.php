<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Archive extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'titre',
        'description',
        'type',
        'module_id',
        'annee_academique_id',
        'user_id',
        'file_path',
        'file_name_original',
        'file_size',
        'mime_type',
        'downloads_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'downloads_count' => 'integer',
    ];

    // L'archive est rattachée à un module précis.
    
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    // L'archive correspond à une année académique précise.
    
    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    // L'utilisateur (souvent chef de filière ou admin) ayant publié l'archive.
     
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
