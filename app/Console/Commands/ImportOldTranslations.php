<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Translation;

class ImportOldTranslations extends Command
{
    protected $signature = 'quran:import-translations';
    protected $description = 'Import translations from old quran table into normalized translations table';

    public function handle()
    {
        $this->info("Starting translations import...");

        // Fetch all rows from old quran table
        $oldRows = DB::table('quran')->get();
        $count = 0;

        foreach ($oldRows as $row) {
            $verseId = $row->id; // direct mapping to verses.id

            // Insert translations
            $translations = [
                ['language' => 'ur', 'translator' => 'Fateh',   'text' => $row->quFateh],
                ['language' => 'ur', 'translator' => 'Mehmood', 'text' => $row->quMehmood],
                ['language' => 'en', 'translator' => 'Mohsin',  'text' => $row->engMohsin],
                ['language' => 'en', 'translator' => 'Taqi',    'text' => $row->engTaqi],
            ];

            foreach ($translations as $t) {
                if (!empty($t['text'])) {
                    Translation::create([
                        'verse_id'   => $verseId,
                        'language'   => $t['language'],
                        'translator' => $t['translator'],
                        'text'       => $t['text'],
                    ]);
                }
            }

            $count++;
        }

        $this->info("Imported translations for {$count} verses successfully!");
    }
}
