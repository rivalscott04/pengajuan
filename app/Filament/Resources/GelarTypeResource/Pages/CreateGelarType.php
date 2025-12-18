<?php

namespace App\Filament\Resources\GelarTypeResource\Pages;

use App\Filament\Resources\GelarTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateGelarType extends CreateRecord
{
    protected static string $resource = GelarTypeResource::class;

    public function getTitle(): string
    {
        return 'Tambah Jenis Gelar';
    }

    public function getHeading(): string
    {
        return 'Tambah Jenis Gelar';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['document_requirements'] = collect($data['document_requirements'] ?? [])
            ->map(function ($item) {
                $label = $item['label'] ?? '';
                $key = $item['key'] ?? Str::slug($label) ?: Str::random(6);

                $sizeNumber = (float) ($item['size_number'] ?? 0);
                $unit = strtoupper($item['size_unit'] ?? 'MB');
                $unit = in_array($unit, ['KB', 'MB']) ? $unit : 'MB';
                $multiplier = $unit === 'KB' ? 1024 : 1024 * 1024;
                $maxSize = (int) max(1, ceil($sizeNumber * $multiplier));

                $mimes = $item['allowed_types'] ?? [];
                if (is_string($mimes)) {
                    $mimes = array_filter(array_map('trim', explode(',', $mimes)));
                }

                return [
                    'key' => $key,
                    'label' => $label,
                    'max_size' => $maxSize,
                    'mimes' => array_values($mimes),
                ];
            })
            ->values()
            ->all();

        return $data;
    }
}





