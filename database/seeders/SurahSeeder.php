<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SurahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $surahs = include database_path('seeders/data/surahs.php');

	foreach ($surahs as $surah) {
    	\App\Models\Surah::firstOrCreate(['id' => $surah['id']], $surah);
	
	}

    }
}
