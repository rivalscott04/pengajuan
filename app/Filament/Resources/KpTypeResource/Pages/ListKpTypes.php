<?php

namespace App\Filament\Resources\KpTypeResource\Pages;

use App\Filament\Resources\KpTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKpTypes extends ListRecords
{
    protected static string $resource = KpTypeResource::class;

    public function getTitle(): string
    {
        return 'Daftar Jenis KP';
    }

    public function getHeading(): string
    {
        return 'Daftar Jenis KP';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Jenis KP'),
        ];
    }
}

