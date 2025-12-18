<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use App\Models\Submission;
use Filament\Resources\Pages\EditRecord;

class EditSubmission extends EditRecord
{
    protected static string $resource = SubmissionResource::class;

    public function getTitle(): string
    {
        return 'Ubah Pengajuan KP';
    }

    public function getHeading(): string
    {
        return 'Ubah Pengajuan KP';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['documents'] = $this->record->documents
            ->mapWithKeys(fn($doc) => [$doc->document_type => $doc->path])
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Submission $record */
        $record = $this->record;
        $state = $this->form->getState();
        SubmissionResource::syncDocuments($record, $state['documents'] ?? []);
    }
}

