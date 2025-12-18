<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin',
            'operator_kabkota',
            'operator_kanwil',
        ];

        foreach ($roles as $role) {
            Role::query()->firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Buat Admin
        $kanwilRegion = Region::query()->where('type', 'kanwil')->first();
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'region_id' => $kanwilRegion?->id,
            ]
        );
        $admin->syncRoles(['admin']);

        // Buat 1 Operator Kanwil
        if ($kanwilRegion) {
            $kanwil = User::query()->firstOrCreate(
                ['email' => 'operator.kanwil@example.com'],
                [
                    'name' => 'Operator Kanwil',
                    'username' => 'op_kanwil',
                    'password' => Hash::make('password'),
                    'region_id' => $kanwilRegion->id,
                ]
            );
            $kanwil->syncRoles(['operator_kanwil']);
        }

        // Buat 10 Operator Kabupaten/Kota
        $kabkotaRegions = Region::query()
            ->where('type', 'kabkota')
            ->orderBy('city_name')
            ->get();

        $kabkotaList = [
            'Kabupaten Bima',
            'Kabupaten Dompu',
            'Kabupaten Lombok Barat',
            'Kabupaten Lombok Tengah',
            'Kabupaten Lombok Timur',
            'Kabupaten Lombok Utara',
            'Kabupaten Sumbawa',
            'Kabupaten Sumbawa Barat',
            'Kota Bima',
            'Kota Mataram',
        ];

        foreach ($kabkotaList as $index => $cityName) {
            $region = $kabkotaRegions->firstWhere('city_name', $cityName);
            
            if ($region) {
                // Generate email dan username dari nama kota
                $slug = strtolower($cityName);
                $slug = str_replace(['kabupaten ', 'kota ', ' '], ['', '', '_'], $slug);
                
                $operator = User::query()->firstOrCreate(
                    ['email' => "operator.{$slug}@example.com"],
                    [
                        'name' => "Operator {$cityName}",
                        'username' => "op_{$slug}",
                        'password' => Hash::make('password'),
                        'region_id' => $region->id,
                    ]
                );
                $operator->syncRoles(['operator_kabkota']);
            }
        }
    }
}

