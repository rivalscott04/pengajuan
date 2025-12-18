<?php

namespace App\Filament\Resources\GelarSubmissionResource\Pages;

use App\Filament\Resources\GelarSubmissionResource;
use App\Models\Submission;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateGelarSubmission extends CreateRecord
{
    protected static string $resource = GelarSubmissionResource::class;

    public function getTitle(): string
    {
        return 'Buat Pengajuan Gelar';
    }

    public function getHeading(): string
    {
        return 'Buat Pengajuan Gelar';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Submission $record */
        $record = $this->record;
        $state = $this->form->getState();
        GelarSubmissionResource::syncDocuments($record, $state['documents'] ?? []);
    }
}


