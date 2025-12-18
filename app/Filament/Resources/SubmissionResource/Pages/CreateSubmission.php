<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use App\Models\Submission;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSubmission extends CreateRecord
{
    protected static string $resource = SubmissionResource::class;

    public function getTitle(): string
    {
        return 'Buat Pengajuan KP';
    }

    public function getHeading(): string
    {
        return 'Buat Pengajuan KP';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        
        // Pastikan operator_kabkota tidak bisa ubah region_id
        // Force region_id sesuai dengan user yang login
        if (Auth::user()?->hasRole('operator_kabkota')) {
            $data['region_id'] = Auth::user()->region_id;
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Submission $record */
        $record = $this->record;
        $state = $this->form->getState();
        SubmissionResource::syncDocuments($record, $state['documents'] ?? []);
    }
}

