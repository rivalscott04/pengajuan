<?php

namespace App\Filament\Resources\GelarSubmissionResource\Pages;

use App\Filament\Resources\GelarSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGelarSubmissions extends ListRecords
{
    protected static string $resource = GelarSubmissionResource::class;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan Gelar';
    }

    public function getHeading(): string
    {
        return 'Daftar Pengajuan Gelar';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Pengajuan Gelar'),
        ];
    }
}


