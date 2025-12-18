<?php

namespace Database\Seeders;

use App\Models\KpType;
use Illuminate\Database\Seeder;

class KpTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // KP Reguler
            [
                'name' => 'Kenaikan Pangkat Reguler',
                'code' => 'reguler',
                'document_requirements' => [
                    [
                        'key' => 'sk_pangkat_terakhir',
                        'label' => 'SK Pangkat Terakhir',
                        'max_size' => 5 * 1024 * 1024,
                        'mimes' => ['pdf'],
                    ],
                    [
                        'key' => 'dp3_skp',
                        'label' => 'DP3/SKP 2 Tahun Terakhir',
                        'max_size' => 5 * 1024 * 1024,
                        'mimes' => ['pdf'],
                    ],
                ],
            ],
            // Penyematan Gelar S1
            [
                'name' => 'Penyematan Gelar S1',
                'code' => 'gelar_s1',
                'document_requirements' => [
                    [
                        'key' => 'ijazah_s1',
                        'label' => 'Ijazah S1',
                        'max_size' => 5 * 1024 * 1024,
                        'mimes' => ['pdf'],
                    ],
                    [
                        'key' => 'transkrip_nilai_s1',
                        'label' => 'Transkrip Nilai S1',
                        'max_size' => 5 * 1024 * 1024,
                        'mimes' => ['pdf'],
                    ],
                ],
            ],
            // Penyematan Gelar S2
            [
                'name' => 'Penyematan Gelar S2',
                'code' => 'gelar_s2',
                'document_requirements' => [
                    [
                        'key' => 'ijazah_s2',
                        'label' => 'Ijazah S2',
                        'max_size' => 5 * 1024 * 1024,
                        'mimes' => ['pdf'],
                    ],
                    [
                        'key' => 'transkrip_nilai_s2',
                        'label' => 'Transkrip Nilai S2',
                        'max_size' => 5 * 1024 * 1024,
                        'mimes' => ['pdf'],
                    ],
                ],
            ],
        ];

        foreach ($types as $type) {
            KpType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}





