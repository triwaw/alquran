<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Verse;
use App\Models\Translation;
use Throwable;

class ImportQuranTranslations extends Command
{
    protected $signature = 'quran:import-translations
                            {--use-config : Read mapping from config/quran.php (default true)}';

    protected $description = 'Import Quran Arabic text and translations from legacy quran table into verses + translations tables.';

    public function handle(): int
    {
        $this->info('Starting import: Quran -> verses & translations');

        // Load mapping from config (fallback internal map if config absent)
        $translationMap = config('quran.translation_map', [
            'en' => ['Mohsin' => 'engMohsin', 'Taqi' => 'engTaqi'],
            'ur' => ['Fateh' => 'quFateh', 'Mehmood' => 'quMehmood'],
        ]);

        $quranTable = config('quran.quran_table', 'quran');

        // Count for progress bar
        $total = (int) DB::table($quranTable)->count();
        $this->info("Rows to process: {$total}");

        if ($total === 0) {
            $this->info('No rows found in table: ' . $quranTable);
            return Command::SUCCESS;
        }

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        // Use cursor() to avoid memory issues for large tables
        try {
            foreach (DB::table($quranTable)->orderBy('id')->cursor() as $row) {

                // 1) Update verses.text_arabic (we assume verses.id matches quran.id)
                // Use update so you don't overwrite surah_id/verse_number if they are already correct.
                Verse::updateOrCreate(
                    ['id' => $row->id],
                    [
                        'surah_id'     => $row->suraNo ?? null,
                        'verse_number' => $row->ayatNo ?? null,
                        'text_arabic'  => $row->quranArabic ?? null,
                    ]
                );

                // 2) Insert/update translations based on mapping
                foreach ($translationMap as $lang => $translators) {
                    foreach ($translators as $translator => $column) {
                        // if property exists and not empty
                        $text = null;
                        if (is_object($row) && property_exists($row, $column)) {
                            $text = $row->{$column};
                        } else {
                            // fallback: try array access
                            $arr = (array) $row;
                            $text = $arr[$column] ?? null;
                        }

                        if ($text === null || trim((string)$text) === '') {
                            continue; // nothing to import for this translator
                        }

                        // updateOrCreate prevents duplicates and allows safe re-run
                        Translation::updateOrCreate(
                            [
                                'verse_id'   => $row->id,
                                'language'   => $lang,
                                'translator' => $translator,
                            ],
                            [
                                'text' => $text,
                            ]
                        );
                    }
                }

                $progress->advance();
            }

            $progress->finish();
            $this->newLine(2);
            $this->info('Import complete ✔');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine(1);
            $this->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
