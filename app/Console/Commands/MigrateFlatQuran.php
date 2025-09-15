<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Surah;
use App\Models\Verse;
use App\Models\Translation;

class MigrateFlatQuran extends Command
{
    protected $signature = 'quran:migrate-flat';
    protected $description = 'Migrate data from flat quran table to normalized tables';

    public function handle()
    {
        $this->info("Starting migration from flat quran table...");

        $rows = DB::table('quran')->get();
        $this->info("Rows to process: " . $rows->count());

        foreach ($rows as $row) {
            // --- Find or create surah ---
            $surah = Surah::firstOrCreate(
                ['number' => $row->suraNo],
                ['arabic_name' => $row->suraName]
            );

           // --- Insert or update verse ---
		$verse = Verse::updateOrCreate(
    		[
        		'surah_id'     => $surah->id,
        		'verse_number' => $row->ayatNo,
    		],
    		[
        		'text_arabic'  => $row->quranArabic,
   		 ]
		);


            // --- Insert translations ---
            $translations = [
                ['language' => 'ur', 'translator' => 'Fateh',   'text' => $row->quFateh],
                ['language' => 'ur', 'translator' => 'Mehmood', 'text' => $row->quMehmood],
                ['language' => 'en', 'translator' => 'Mohsin',  'text' => $row->engMohsin],
                ['language' => 'en', 'translator' => 'Taqi',    'text' => $row->engTaqi],
            ];

            foreach ($translations as $t) {
                if (!empty($t['text'])) {
                    Translation::updateOrCreate(
                        [
                            'verse_id'   => $verse->id,
                            'language'   => $t['language'],
                            'translator' => $t['translator'],
                        ],
                        ['text' => $t['text']]
                    );
                }
            }
        }

        $this->info("Migration complete!");
    }
}
