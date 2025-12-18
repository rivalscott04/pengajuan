<?php

namespace Database\Seeders;

use App\Models\KpType;
use Illuminate\Database\Seeder;

class KpTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
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
            [
                'name' => 'Kenaikan Pangkat Pilihan',
                'code' => 'pilihan',
                'document_requirements' => [
                    [
                        'key' => 'sk_jabatan',
                        'label' => 'SK Jabatan',
                        'max_size' => 5 * 1024 * 1024,
                        'mimes' => ['pdf'],
                    ],
                    [
                        'key' => 'sertifikat_diklat',
                        'label' => 'Sertifikat Diklat',
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




