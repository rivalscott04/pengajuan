<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Exports\SubmissionExport;
use App\Filament\Resources\SubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan KP';
    }

    public function getHeading(): string
    {
        return 'Daftar Pengajuan KP';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Pengajuan'),
            Actions\Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => Auth::user()?->hasAnyRole(['admin', 'operator_kanwil']))
                ->action(function () {
                    // Get the filtered query from the table
                    $query = $this->getFilteredTableQuery();
                    
                    $fileName = 'rekap-pengajuan-kp-' . now()->format('Y-m-d-His') . '.xlsx';
                    
                    return Excel::download(new SubmissionExport($query), $fileName);
                }),
        ];
    }
}

