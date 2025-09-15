<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reciter;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;


class ReciterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reciters = [
            [
                'name' => 'Mishary Rashid Alafasy',
                'style' => 'Murattal',
                'country' => 'Kuwait',
                'language' => 'Arabic',
            ],
            [
                'name' => 'Abdul Rahman Al-Sudais',
                'style' => 'Murattal',
                'country' => 'Saudi Arabia',
                'language' => 'Arabic',
            ],
            [
                'name' => 'Saad Al-Ghamdi',
                'style' => 'Murattal',
                'country' => 'Saudi Arabia',
                'language' => 'Arabic',
            ],
            [
                'name' => 'Maher Al-Muaiqly',
                'style' => 'Murattal',
                'country' => 'Saudi Arabia',
                'language' => 'Arabic',
            ],
            [
                'name' => 'Abu Bakr Al-Shatri',
                'style' => 'Murattal',
                'country' => 'Saudi Arabia',
                'language' => 'Arabic',
            ],
        ];

        foreach ($reciters as $reciter) {
            Reciter::firstOrCreate(
                ['name' => $reciter['name']], // avoid duplicates
                $reciter
            );
        }
    }
}
