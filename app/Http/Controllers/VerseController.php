<?php

namespace App\Http\Controllers;

use App\Models\Verse;
use App\Models\Surah;
use Illuminate\Http\Request;

class VerseController extends Controller
{
    // 🔹 Get one specific verse with details
    public function show($surahId, $verseNumber)
    {
        $verse = Verse::where('surah_id', $surahId)
            ->where('verse_number', $verseNumber)
            ->with(['translations', 'tafsir', 'words', 'audio.reciter'])
            ->firstOrFail();

        return $verse;
    }

    // 🔹 Search verses by Arabic or translation text
    public function search(Request $request)
    {
        $query = $request->input('query');
        $lang  = $request->input('lang', 'en'); // default to English

        $results = Verse::with('translations')
            ->where('text_arabic', 'LIKE', "%$query%")
            ->orWhereHas('translations', function ($q) use ($query, $lang) {
                $q->where('language_code', $lang)
                  ->where('text', 'LIKE', "%$query%");
            })
            ->get();

        return $results;
    }
}
