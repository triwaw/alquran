<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reciter extends Model
{
    protected $table = 'reciters';

    protected $fillable = [
        'name',
        'style',
        'country',
        'language',
    ];

    public function audios()
    {
        return $this->hasMany(Audio::class);
    }
}
