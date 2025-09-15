<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Surah;
use App\Models\Verse;
use App\Models\Translation;

class ImportQuran extends Command
{
    protected $signature = 'quran:import';
    protected $description = 'Import Quran data from old table into new schema';

    public function handle()
    {
        $rows = DB::table('quran')->orderBy('suraNo')->orderBy('ayatNo')->get();

        foreach ($rows as $row) {
            // Find surah_id
            $surah = Surah::where('number', $row->suraNo)->first();

            if (!$surah) {
                $this->warn("Surah {$row->suraNo} not found, skipping...");
                continue;
            }

            // Insert verse
            $verse = Verse::create([
                'surah_id'     => $surah->id,
                'verse_number' => $row->ayatNo,
                'text_arabic'  => $row->quranArabic,
            ]);

            // Insert translations
      // Insert translations
if ($row->engMohsin) {
    Translation::create([
        'verse_id' => $verse->id,
        'language' => 'en',
        'text'     => $row->engMohsin,
    ]);
}

if ($row->engTaqi) {
    Translation::create([
        'verse_id' => $verse->id,
        'language' => 'en',
        'text'     => $row->engTaqi,
    ]);
}

if ($row->quFateh) {
    Translation::create([
        'verse_id' => $verse->id,
        'language' => 'ur',
        'text'     => $row->quFateh,
    ]);
}

if ($row->quMehmood) {
    Translation::create([
        'verse_id' => $verse->id,
        'language' => 'ur',
        'text'     => $row->quMehmood,
    ]);
}
        }

        $this->info('Quran import completed successfully.');
    }
}