<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;


class Verse extends Model
{
    use HasFactory;

    protected $table = 'verses';

    protected $fillable = [
        'surah_id',
        'verse_number',
        'text_arabic',
        'text_clean',
        'page_number',
        'juz_number',
        'hizb_number',
        'rub_number',
        'sajda',
    ];

    // 🔗 A Verse belongs to a Surah
    public function surah()
    {
        return $this->belongsTo(Surah::class);
    }

    // 🔗 A Verse has many Translations
    public function translations()
    {
     //   return $this->hasMany(Translation::class);
        return $this->hasMany(\App\Models\Translation::class);
    }

    // 🔗 A Verse has many Tafsir entries
    public function tafsir()
    {
        return $this->hasMany(Tafsir::class);
    }

    // 🔗 A Verse has many Words (word-by-word analysis)
    public function words()
    {
        return $this->hasMany(Word::class);
    }

    // 🔗 A Verse has many Audio recitations
    public function audio()
    {
        return $this->hasMany(Audio::class);
    }
}
