<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Surah;
use App\Models\Verse;

class QuranController extends Controller
{
    public function index()
    {
        // Show the input form only
        return view('quran.index');
    }

    public function show(Request $request)
    {
        $surahNumber = $request->get('surah');

        $surah = Surah::where('number', $surahNumber)->firstOrFail();

        $verses = Verse::with('translations')
            ->where('surah_id', $surah->id)
            ->orderBy('verse_number')
            ->get();

        return view('quran.show', compact('surah', 'verses'));
    }
}
