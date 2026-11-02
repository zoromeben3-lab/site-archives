<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Historique extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'archive_id',
        'description',
    ];

    // L'utilisateur qui a effectué l'action.
     
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // L'archive concernée (peut être null si l'archive a été supprimée définitivement).
    
    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class)->withTrashed();
    }
}
