<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surah extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'arabic_name',
        'english_name',
        'urdu_name',
        'revelation_type',
        'verses_count',
        'raku_count',
    ];

    public function verses()
    {
        return $this->hasMany(Verse::class);
    }
}
