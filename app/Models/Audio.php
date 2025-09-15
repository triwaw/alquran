<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audio extends Model
{
    use HasFactory;

    protected $table = 'audio';

    protected $fillable = [
        'verse_id',
        'reciter_id',
        'url',
        'duration',
    ];

    // 🔗 An Audio belongs to a Verse
    public function verse()
    {
        return $this->belongsTo(Verse::class);
    }

    // 🔗 An Audio belongs to a Reciter
    public function reciter()
    {
        return $this->belongsTo(Reciter::class);
    }
}
