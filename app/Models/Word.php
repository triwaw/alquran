<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    use HasFactory;

    protected $table = 'words';

    protected $fillable = [
        'verse_id',
        'position',
        'text_arabic',
        'text_clean',
        'root',
        'lemma',
        'translation_en',
        'translation_ur',
    ];

    // 🔗 A Word belongs to a Verse
    public function verse()
    {
        return $this->belongsTo(Verse::class);
    }
}
