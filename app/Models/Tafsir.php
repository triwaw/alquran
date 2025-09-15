<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tafsir extends Model
{
    use HasFactory;

    protected $table = 'tafsir';

    protected $fillable = [
        'verse_id',
        'author',
        'text',
    ];

    // 🔗 A Tafsir belongs to a Verse
    public function verse()
    {
        return $this->belongsTo(Verse::class);
    }
}
