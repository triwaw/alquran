<?php

namespace App\Http\Controllers;

use App\Models\Surah;
use Illuminate\Http\Request;

class SurahController extends Controller
{
    // 🔹 List all Surahs

public function index()
{
    return Surah::select(
        'id',
        'number',
        'arabic_name',
        'english_name',
        'urdu_name',
        'revelation_type',
        'verses_count'
    )
    ->orderBy('id')
    ->get();
}



    // 🔹 Get a single Surah with all verses
    public function show($id)
    {
        return Surah::with('verses')->findOrFail($id);
    }
}
