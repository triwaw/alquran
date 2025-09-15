<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Surah;
use App\Models\Verse;
use App\Models\Translation;

class QuranSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------
        // Surah 1 - Al-Fatihah
        // -------------------------
        $surah1 = Surah::create([
            'number'          => 1,
            'arabic_name'     => 'الفاتحة',
            'english_name'    => 'Al-Fatihah',
            'urdu_name'       => 'الفاتحہ',
            'revelation_type' => 'Meccan',
            'verses_count'    => 7,
            'raku_count'      => 1,
        ]);

        $verses1 = [
            1 => 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
            2 => 'الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ',
            3 => 'الرَّحْمَٰنِ الرَّحِيمِ',
            4 => 'مَالِكِ يَوْمِ الدِّينِ',
            5 => 'إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ',
            6 => 'اهْدِنَا الصِّرَاطَ الْمُسْتَقِيمَ',
            7 => 'صِرَاطَ الَّذِينَ أَنْعَمْتَ عَلَيْهِمْ غَيْرِ الْمَغْضُوبِ عَلَيْهِمْ وَلَا الضَّالِّينَ',
        ];

        foreach ($verses1 as $num => $text) {
            $verse = Verse::create([
                'surah_id'     => $surah1->id,
                'verse_number' => $num,
                'text_arabic'  => $text,
            ]);

            Translation::create([
                'verse_id'   => $verse->id,
                'language'   => 'en',
                'translator' => 'SampleEnglish',
                'text'       => 'Sample English translation for verse ' . $num,
            ]);

            Translation::create([
                'verse_id'   => $verse->id,
                'language'   => 'ur',
                'translator' => 'SampleUrdu',
                'text'       => 'نمونہ اردو ترجمہ آیت ' . $num,
            ]);
        }

        // -------------------------
        // Surah 113 - Al-Falaq
        // -------------------------
        $surah113 = Surah::create([
            'number'          => 113,
            'arabic_name'     => 'الفلق',
            'english_name'    => 'Al-Falaq',
            'urdu_name'       => 'الفلق',
            'revelation_type' => 'Meccan',
            'verses_count'    => 5,
            'raku_count'      => 1,
        ]);

        $verses113 = [
            1 => 'قُلْ أَعُوذُ بِرَبِّ الْفَلَقِ',
            2 => 'مِن شَرِّ مَا خَلَقَ',
            3 => 'وَمِن شَرِّ غَاسِقٍ إِذَا وَقَبَ',
            4 => 'وَمِن شَرِّ النَّفَّاثَاتِ فِي الْعُقَدِ',
            5 => 'وَمِن شَرِّ حَاسِدٍ إِذَا حَسَدَ',
        ];

        foreach ($verses113 as $num => $text) {
            $verse = Verse::create([
                'surah_id'     => $surah113->id,
                'verse_number' => $num,
                'text_arabic'  => $text,
            ]);

            Translation::create([
                'verse_id'   => $verse->id,
                'language'   => 'en',
                'translator' => 'SampleEnglish',
                'text'       => 'Sample English translation for verse ' . $num,
            ]);
        }

        // -------------------------
        // Surah 114 - An-Nas
        // -------------------------
        $surah114 = Surah::create([
            'number'          => 114,
            'arabic_name'     => 'الناس',
            'english_name'    => 'An-Nas',
            'urdu_name'       => 'الناس',
            'revelation_type' => 'Meccan',
            'verses_count'    => 6,
            'raku_count'      => 1,
        ]);

        $verses114 = [
            1 => 'قُلْ أَعُوذُ بِرَبِّ النَّاسِ',
            2 => 'مَلِكِ النَّاسِ',
            3 => 'إِلَٰهِ النَّاسِ',
            4 => 'مِن شَرِّ الْوَسْوَاسِ الْخَنَّاسِ',
            5 => 'الَّذِي يُوَسْوِسُ فِي صُدُورِ النَّاسِ',
            6 => 'مِنَ الْجِنَّةِ وَالنَّاسِ',
        ];

        foreach ($verses114 as $num => $text) {
            $verse = Verse::create([
                'surah_id'     => $surah114->id,
                'verse_number' => $num,
                'text_arabic'  => $text,
            ]);

            Translation::create([
                'verse_id'   => $verse->id,
                'language'   => 'en',
                'translator' => 'SampleEnglish',
                'text'       => 'Sample English translation for verse ' . $num,
            ]);
        }
    }
}
