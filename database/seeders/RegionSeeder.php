<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['city_name' => 'Kabupaten Bima', 'type' => 'kabkota'],
            ['city_name' => 'Kabupaten Dompu', 'type' => 'kabkota'],
            ['city_name' => 'Kabupaten Lombok Barat', 'type' => 'kabkota'],
            ['city_name' => 'Kabupaten Lombok Tengah', 'type' => 'kabkota'],
            ['city_name' => 'Kabupaten Lombok Timur', 'type' => 'kabkota'],
            ['city_name' => 'Kabupaten Lombok Utara', 'type' => 'kabkota'],
            ['city_name' => 'Kabupaten Sumbawa', 'type' => 'kabkota'],
            ['city_name' => 'Kabupaten Sumbawa Barat', 'type' => 'kabkota'],
            ['city_name' => 'Kota Bima', 'type' => 'kabkota'],
            ['city_name' => 'Kota Mataram', 'type' => 'kabkota'],
            ['city_name' => 'Kanwil Kemenag Provinsi NTB', 'type' => 'kanwil'],
        ];

        foreach ($regions as $region) {
            Region::query()->firstOrCreate(
                ['city_name' => $region['city_name']],
                $region
            );
        }
    }
}

