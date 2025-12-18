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

        $kanwilRegion = Region::query()->where('type', 'kanwil')->first();
        $kabkotaRegion = Region::query()->where('city_name', 'Kabupaten Lombok Timur')->first();

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'region_id' => $kanwilRegion?->id,
            ]
        );
        $admin->syncRoles(['admin']);

        if ($kabkotaRegion) {
            $kabkota = User::query()->firstOrCreate(
                ['email' => 'operator.kab@example.com'],
                [
                    'name' => 'Operator Kabupaten',
                    'password' => Hash::make('password'),
                    'region_id' => $kabkotaRegion->id,
                ]
            );
            $kabkota->syncRoles(['operator_kabkota']);
        }

        if ($kanwilRegion) {
            $kanwil = User::query()->firstOrCreate(
                ['email' => 'operator.kanwil@example.com'],
                [
                    'name' => 'Operator Kanwil',
                    'password' => Hash::make('password'),
                    'region_id' => $kanwilRegion->id,
                ]
            );
            $kanwil->syncRoles(['operator_kanwil']);
        }
    }
}

