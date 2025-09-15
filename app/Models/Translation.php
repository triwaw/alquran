<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'verse_id',
        'language',
        'translator',
        'text',
    ];

    // Define the inverse of the relationship to Verse
    public function verse()
    {
        return $this->belongsTo(Verse::class);
    }
}
