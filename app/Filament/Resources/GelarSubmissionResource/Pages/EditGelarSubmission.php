<?php

namespace App\Filament\Resources\GelarSubmissionResource\Pages;

use App\Filament\Resources\GelarSubmissionResource;
use App\Models\Submission;
use Filament\Resources\Pages\EditRecord;

class EditGelarSubmission extends EditRecord
{
    protected static string $resource = GelarSubmissionResource::class;

    public function getTitle(): string
    {
        return 'Ubah Pengajuan Gelar';
    }

    public function getHeading(): string
    {
        return 'Ubah Pengajuan Gelar';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['documents'] = $this->record->documents
            ->mapWithKeys(fn ($doc) => [$doc->document_type => $doc->path])
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Submission $record */
        $record = $this->record;
        $state = $this->form->getState();
        GelarSubmissionResource::syncDocuments($record, $state['documents'] ?? []);
    }
}


