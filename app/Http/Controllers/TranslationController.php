<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use Illuminate\Http\Request;




   // public function index()     {
   //     return response()->json(['status' => 'ok', 'data' => []]);
   
   




class TranslationController extends Controller
{
    // 🔹 Get all translations (with optional filters)
    public function index(Request $request)
    {
        $language = $request->input('lang');    // e.g. "en", "ur", "fr"
        $author   = $request->input('author');  // e.g. "Pickthall", "Maududi"

        $query = Translation::query();

        if ($language) {
            $query->where('language_code', $language);
        }

        if ($author) {
            $query->where('author', 'LIKE', "%$author%");
        }

        return $query->paginate(20);
    }

    // 🔹 Get translations for a specific verse
    public function show($surahId, $verseNumber, Request $request)
    {
        $language = $request->input('lang');   // optional filter
        $author   = $request->input('author'); // optional filter

        $query = Translation::whereHas('verse', function ($q) use ($surahId, $verseNumber) {
            $q->where('surah_id', $surahId)
              ->where('verse_number', $verseNumber);
        });

        if ($language) {
            $query->where('language_code', $language);
        }

        if ($author) {
            $query->where('author', 'LIKE', "%$author%");
        }

        return $query->get();
    }
}
