<?php

namespace App\Filament\Resources\GelarTypeResource\Pages;

use App\Filament\Resources\GelarTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGelarTypes extends ListRecords
{
    protected static string $resource = GelarTypeResource::class;

    public function getTitle(): string
    {
        return 'Daftar Jenis Gelar';
    }

    public function getHeading(): string
    {
        return 'Daftar Jenis Gelar';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Jenis Gelar'),
        ];
    }
}


