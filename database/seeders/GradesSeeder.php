<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;

class GradesSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['code' => 'I/a', 'category' => 'Golongan I', 'rank' => 'Juru Muda'],
            ['code' => 'I/b', 'category' => 'Golongan I', 'rank' => 'Juru Muda Tingkat I'],
            ['code' => 'I/c', 'category' => 'Golongan I', 'rank' => 'Juru'],
            ['code' => 'I/d', 'category' => 'Golongan I', 'rank' => 'Juru Tingkat I'],
            ['code' => 'II/a', 'category' => 'Golongan II', 'rank' => 'Pengatur Muda'],
            ['code' => 'II/b', 'category' => 'Golongan II', 'rank' => 'Pengatur Muda Tingkat I'],
            ['code' => 'II/c', 'category' => 'Golongan II', 'rank' => 'Pengatur'],
            ['code' => 'II/d', 'category' => 'Golongan II', 'rank' => 'Pengatur Tingkat I'],
            ['code' => 'III/a', 'category' => 'Golongan III', 'rank' => 'Penata Muda'],
            ['code' => 'III/b', 'category' => 'Golongan III', 'rank' => 'Penata Muda Tingkat I'],
            ['code' => 'III/c', 'category' => 'Golongan III', 'rank' => 'Penata'],
            ['code' => 'III/d', 'category' => 'Golongan III', 'rank' => 'Penata Tingkat I'],
            ['code' => 'IV/a', 'category' => 'Golongan IV', 'rank' => 'Pembina'],
            ['code' => 'IV/a /Esselon II', 'category' => 'Gol IV/Esselon II', 'rank' => 'Pembina'],
            ['code' => 'IV/b', 'category' => 'Golongan IV', 'rank' => 'Pembina Tingkat I'],
            ['code' => 'IV/b /Esselon II', 'category' => 'Gol IV/Esselon II', 'rank' => 'Pembina Tingkat I'],
            ['code' => 'IV/c', 'category' => 'Golongan IV', 'rank' => 'Pembina Utama Muda'],
            ['code' => 'IV/c /Esselon II', 'category' => 'Gol IV/Esselon II', 'rank' => 'Pembina Utama Muda'],
            ['code' => 'IV/d', 'category' => 'Golongan IV', 'rank' => 'Pembina Utama Madya'],
            ['code' => 'IV/d /Esselon II', 'category' => 'Gol IV/Esselon II', 'rank' => 'Pembina Utama Madya'],
            ['code' => 'IV/e', 'category' => 'Golongan IV', 'rank' => 'Pembina Utama'],
            ['code' => 'IV/e /Esselon II', 'category' => 'Gol IV/Esselon II', 'rank' => 'Pembina Utama'],
            ['code' => 'TKS', 'category' => 'TKS', 'rank' => 'TKS'],
        ];

        foreach ($grades as $g) {
            Grade::firstOrCreate([
                'code' => $g['code'],
                'category' => $g['category'],
                'rank' => $g['rank'],
            ]);
        }
    }
}
